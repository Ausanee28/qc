<?php

use App\Services\DashboardMetricsService;
use App\Services\AggregateMetricsService;
use App\Http\Controllers\ReceiveJobController;
use App\Support\DashboardCache;
use App\Support\PerformanceBaselineCollector;
use App\Support\ReportCacheKey;
use App\Support\ReportCacheVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('qc:baseline {--output= : Output path under storage/app} {--pretty : Pretty-print JSON output}', function (PerformanceBaselineCollector $collector) {
    $payload = $collector->collect();
    $defaultPath = 'performance/baseline-' . now()->format('Y-m-d-His') . '.json';
    $path = (string) ($this->option('output') ?: $defaultPath);
    $flags = JSON_UNESCAPED_SLASHES;

    if ($this->option('pretty')) {
        $flags |= JSON_PRETTY_PRINT;
    }

    $json = json_encode($payload, $flags);

    if ($json === false) {
        $this->error('Failed to encode baseline payload to JSON.');
        return self::FAILURE;
    }

    Storage::disk('local')->put($path, $json);

    $this->info('Baseline written: storage/app/' . $path);
    $this->line('Database: ' . ($payload['database'] ?? 'unknown'));
    $this->line('Generated at: ' . ($payload['generated_at'] ?? 'unknown'));

    return self::SUCCESS;
})->purpose('Collect database/query baseline metrics for scale monitoring');

Artisan::command('qc:aggregate-metrics {--days=120 : Number of recent days to refresh}', function (AggregateMetricsService $service) {
    $days = max(1, (int) $this->option('days'));
    $from = now()->subDays($days)->startOfDay();
    $to = now()->endOfDay();

    $payload = $service->refresh($from, $to);
    $version = ReportCacheVersion::bump();
    Cache::forget('performance.inspectors.30d');
    Cache::forget('performance.details.30d.recent50');

    $this->info('Aggregation refresh completed.');
    $this->line("Window: {$payload['from']} -> {$payload['to']}");
    $this->line('report_daily_rows=' . ($payload['report_daily_rows'] ?? 0));
    $this->line('report_monthly_rows=' . ($payload['report_monthly_rows'] ?? 0));
    $this->line('performance_daily_inspector_rows=' . ($payload['performance_daily_inspector_rows'] ?? 0));
    $this->line('report_cache_version=' . $version);

    return self::SUCCESS;
})->purpose('Refresh daily/monthly aggregate tables for report/performance paths');

Artisan::command('qc:redis-health {--fail-on-error : Exit non-zero when Redis is unavailable}', function () {
    $started = hrtime(true);

    try {
        $connection = (string) config('database.redis.cache.connection', 'default');
        $pong = Redis::connection($connection)->ping();
        $elapsedMs = round((hrtime(true) - $started) / 1_000_000, 2);
        Cache::put('qc.redis.health.last_ok_at', now()->toDateTimeString(), now()->addMinutes(10));
        Cache::put('qc.redis.health.last_latency_ms', $elapsedMs, now()->addMinutes(10));
        $this->info('Redis health: OK (' . $pong . ')');
        $this->line('Latency: ' . $elapsedMs . 'ms');

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $message = $e->getMessage();
        Log::critical('Redis health check failed.', ['message' => $message]);
        $this->error('Redis health: FAIL - ' . $message);

        return $this->option('fail-on-error') ? self::FAILURE : self::SUCCESS;
    }
})->purpose('Check Redis availability and cache response latency');

Artisan::command('qc:partition-aggregates {--years=3 : Future years to pre-create monthly partitions} {--execute : Apply generated SQL}', function () {
    if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->warn('Partition DDL is supported only for mysql/mariadb drivers.');
        return self::SUCCESS;
    }

    $years = max(1, (int) $this->option('years'));
    $startMonth = now()->startOfYear()->subYear();
    $endMonth = now()->startOfYear()->addYears($years)->endOfYear();
    $partitions = [];
    $partitionDefinitions = [];

    for ($cursor = $startMonth->copy(); $cursor->lte($endMonth); $cursor->addMonth()) {
        $partitionName = 'p' . $cursor->format('Ym');
        $lessThanDate = $cursor->copy()->addMonth()->startOfMonth()->format('Y-m-d');
        $definition = "PARTITION {$partitionName} VALUES LESS THAN ('{$lessThanDate}')";
        $partitions[] = $definition;
        $partitionDefinitions[$partitionName] = $definition;
    }
    $partitions[] = 'PARTITION pmax VALUES LESS THAN (MAXVALUE)';
    $partitionSql = implode(",\n    ", $partitions);

    $tables = [
        'report_daily_aggregates' => 'date_key',
        'performance_daily_inspector_aggregates' => 'date_key',
    ];

    foreach ($tables as $table => $column) {
        $sql = "ALTER TABLE {$table} PARTITION BY RANGE COLUMNS({$column}) (\n    {$partitionSql}\n);";

        if (!$this->option('execute')) {
            $this->line('');
            $this->line("-- {$table}");
            $this->line($sql);
            continue;
        }

        try {
            $existingPartitions = DB::table('information_schema.PARTITIONS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->whereNotNull('PARTITION_NAME')
                ->pluck('PARTITION_NAME')
                ->map(fn ($name) => (string) $name)
                ->all();

            if (count($existingPartitions) === 0) {
                DB::statement($sql);
                $this->info("Partition plan applied: {$table}");
                continue;
            }

            $existingMap = array_flip($existingPartitions);
            $missingNames = array_values(array_filter(
                array_keys($partitionDefinitions),
                static fn (string $name) => !isset($existingMap[$name])
            ));

            if (count($missingNames) === 0) {
                $this->info("Partition plan already up to date: {$table}");
                continue;
            }

            if (!isset($existingMap['pmax'])) {
                $this->warn("Partition apply skipped for {$table}: existing table is partitioned but pmax is missing.");
                continue;
            }

            $reorganizeDefinitions = array_map(
                static fn (string $name) => $partitionDefinitions[$name],
                $missingNames
            );
            $reorganizeDefinitions[] = 'PARTITION pmax VALUES LESS THAN (MAXVALUE)';
            $reorganizeSql = "ALTER TABLE {$table} REORGANIZE PARTITION pmax INTO (\n    " . implode(",\n    ", $reorganizeDefinitions) . "\n);";

            DB::statement($reorganizeSql);
            $this->info("Partition plan extended: {$table} (+" . count($missingNames) . " monthly partition(s))");
        } catch (\Throwable $e) {
            $this->warn("Partition apply failed for {$table}: " . $e->getMessage());
        }
    }

    if (!$this->option('execute')) {
        $this->info('Generated partition SQL. Re-run with --execute to apply.');
    }

    return self::SUCCESS;
})->purpose('Generate/apply monthly partitions for aggregate tables');

Artisan::command(
    'qc:scale-benchmark
    {--headers=50000 : Number of synthetic Transaction_Header rows}
    {--details-per-header=4 : Number of synthetic Transaction_Detail rows per header}
    {--chunk=1000 : Insert chunk size}
    {--window-days=30 : Date window (days) for report/performance queries}
    {--open-ratio=67 : Percent of synthetic jobs left open (0-100)}
    {--out= : Output path under storage/app (JSON report)}
    {--gate-receive-count-ms=500 : PASS threshold for receive-job total count query}
    {--gate-receive-page-ms=800 : PASS threshold for receive-job page query}
    {--gate-pending-open-ms=1200 : PASS threshold for execute-test pending-jobs query}
    {--gate-results-count-ms=2500 : PASS threshold for execute-test results count query}
    {--gate-results-page-ms=800 : PASS threshold for execute-test results page query}
    {--gate-report-summary-ms=2500 : PASS threshold for report summary query}
    {--gate-report-page-ms=3000 : PASS threshold for report page query}
    {--gate-performance-inspectors-ms=2000 : PASS threshold for performance inspectors query}
    {--gate-performance-details-ms=1200 : PASS threshold for performance details query}',
    function () {
        $headers = max(1, (int) $this->option('headers'));
        $detailsPerHeader = max(1, (int) $this->option('details-per-header'));
        $chunk = max(100, (int) $this->option('chunk'));
        $windowDays = max(1, (int) $this->option('window-days'));
        $openRatio = max(0, min(100, (int) $this->option('open-ratio')));
        $detailRowsTarget = $headers * $detailsPerHeader;

        $externalId = DB::table('External_Users')->value('external_id');
        $internalId = DB::table('Internal_Users')->value('user_id');
        $methodId = DB::table('Test_Methods')->value('method_id');

        if (!$externalId || !$internalId || !$methodId) {
            $this->error('Missing master data for benchmark seed. Ensure External_Users, Internal_Users, and Test_Methods have at least one row.');
            return self::FAILURE;
        }

        $pendingJobsWindow = 500;
        $gateMs = [
            'receive_job.page_1' => (float) $this->option('gate-receive-page-ms'),
            'execute_test.pending_open_jobs' => (float) $this->option('gate-pending-open-ms'),
            'execute_test.results_page_1' => (float) $this->option('gate-results-page-ms'),
            'report.summary_window' => (float) $this->option('gate-report-summary-ms'),
            'report.page_1_window' => (float) $this->option('gate-report-page-ms'),
            'performance.inspectors_window' => (float) $this->option('gate-performance-inspectors-ms'),
            'performance.details_recent_50' => (float) $this->option('gate-performance-details-ms'),
        ];

        $fetchAutoIncrement = static function (string $table): ?int {
            $row = DB::table('information_schema.tables')
                ->select('AUTO_INCREMENT')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', $table)
                ->first();

            if (!$row || !isset($row->AUTO_INCREMENT)) {
                return null;
            }

            return $row->AUTO_INCREMENT !== null ? (int) $row->AUTO_INCREMENT : null;
        };

        $restoreAutoIncrement = static function (string $table, int $target): void {
            $safeTarget = max(1, $target);
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$safeTarget}");
        };

        $headerMaxBefore = (int) (DB::table('Transaction_Header')->max('transaction_id') ?? 0);
        $detailMaxBefore = (int) (DB::table('Transaction_Detail')->max('detail_id') ?? 0);
        $headerAutoBefore = $fetchAutoIncrement('Transaction_Header');
        $detailAutoBefore = $fetchAutoIncrement('Transaction_Detail');

        $metrics = [];
        $measure = function (string $label, callable $callback) use (&$metrics) {
            $started = hrtime(true);
            $result = $callback();
            $elapsedMs = round((hrtime(true) - $started) / 1_000_000, 2);
            $rows = null;
            $value = null;

            if ($result instanceof \Illuminate\Support\Collection || $result instanceof \Illuminate\Database\Eloquent\Collection) {
                $rows = $result->count();
            } elseif (is_array($result)) {
                $rows = count($result);
            } elseif (is_scalar($result) || $result === null) {
                $value = $result;
            }

            $metrics[$label] = [
                'ms' => $elapsedMs,
                'rows' => $rows,
                'value' => $value,
            ];

            return $result;
        };

        $rolledBack = false;
        $insertedHeaders = 0;
        $insertedDetails = 0;

        $this->line('Scale benchmark configuration:');
        $this->line("- synthetic headers: {$headers}");
        $this->line("- synthetic details: {$detailRowsTarget}");
        $this->line("- open ratio: {$openRatio}%");
        $this->line("- chunk size: {$chunk}");
        $this->line("- window days: {$windowDays}");

        DB::beginTransaction();

        try {
            $seedStarted = hrtime(true);
            $now = now();
            $rangeDays = max(90, $windowDays * 3);

            for ($offset = 0; $offset < $headers; $offset += $chunk) {
                $batchSize = min($chunk, $headers - $offset);
                $headerRows = [];
                $detailRows = [];

                for ($index = 0; $index < $batchSize; $index++) {
                    $sequence = $offset + $index + 1;
                    $transactionId = $headerMaxBefore + $sequence;
                    $receivedAt = $now->copy()
                        ->subDays($sequence % $rangeDays)
                        ->subMinutes($sequence % 1440);
                    $isOpen = ($sequence % 100) < $openRatio;
                    $returnAt = $isOpen
                        ? null
                        : $receivedAt->copy()->addHours(1 + ($sequence % 8));

                    $headerRows[] = [
                        'transaction_id' => $transactionId,
                        'external_id' => $externalId,
                        'internal_id' => $internalId,
                        'detail' => 'Synthetic load #' . $transactionId,
                        'dmc' => 'DMC-' . str_pad((string) (($sequence % 900) + 100), 3, '0', STR_PAD_LEFT),
                        'line' => 'L' . (($sequence % 12) + 1),
                        'receive_date' => $receivedAt->format('Y-m-d H:i:s'),
                        'return_date' => $returnAt?->format('Y-m-d H:i:s'),
                        'deleted_at' => null,
                    ];

                    for ($detailIndex = 0; $detailIndex < $detailsPerHeader; $detailIndex++) {
                        $start = $receivedAt->copy()->addMinutes(($detailIndex + 1) * 6);
                        $duration = 120 + (($detailIndex * 45 + $sequence) % 480);
                        $end = $start->copy()->addSeconds($duration);

                        $detailRows[] = [
                            'transaction_id' => $transactionId,
                            'method_id' => $methodId,
                            'internal_id' => $internalId,
                            'start_time' => $start->format('Y-m-d H:i:s'),
                            'end_time' => $end->format('Y-m-d H:i:s'),
                            'duration_sec' => $duration,
                            'max_value' => (string) (100 + ($detailIndex % 10)),
                            'min_value' => (string) (10 + ($detailIndex % 5)),
                            'judgement' => (($sequence + $detailIndex) % 5 === 0) ? 'NG' : 'OK',
                            'remark' => (($sequence + $detailIndex) % 7 === 0) ? 'Synthetic NG sample' : null,
                            'deleted_at' => null,
                        ];
                    }
                }

                DB::table('Transaction_Header')->insert($headerRows);
                DB::table('Transaction_Detail')->insert($detailRows);

                $insertedHeaders += count($headerRows);
                $insertedDetails += count($detailRows);

                if ($insertedHeaders % ($chunk * 10) === 0 || $insertedHeaders === $headers) {
                    $this->line("Seeded {$insertedHeaders}/{$headers} headers...");
                }
            }

            $seedMs = round((hrtime(true) - $seedStarted) / 1_000_000, 2);
            $windowFrom = now()->subDays($windowDays)->startOfDay();
            $windowTo = now()->endOfDay();

            $measure('receive_job.total_count', function () {
                $jobsQuery = DB::table('Transaction_Header as TH')
                    ->leftJoin('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
                    ->leftJoin('Internal_Users as IU', 'TH.internal_id', '=', 'IU.user_id')
                    ->whereNull('TH.deleted_at');

                return (clone $jobsQuery)->count();
            });

            $measure('receive_job.page_1', function () {
                return DB::table('Transaction_Header as TH')
                    ->leftJoin('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
                    ->leftJoin('Internal_Users as IU', 'TH.internal_id', '=', 'IU.user_id')
                    ->whereNull('TH.deleted_at')
                    ->select('TH.transaction_id', 'TH.receive_date', 'TH.return_date', 'TH.dmc', 'TH.line')
                    ->selectRaw('EU.external_name as external_name')
                    ->selectRaw('IU.name as internal_name')
                    ->selectRaw('(SELECT COUNT(*) FROM Transaction_Detail TD WHERE TD.transaction_id = TH.transaction_id AND TD.deleted_at IS NULL) as details_count')
                    ->orderByDesc('TH.receive_date')
                    ->limit(20)
                    ->get();
            });

            $measure('execute_test.pending_open_jobs', function () use ($pendingJobsWindow) {
                return DB::table('Transaction_Header')
                    ->whereNull('return_date')
                    ->whereNull('deleted_at')
                    ->orderByDesc('receive_date')
                    ->limit($pendingJobsWindow)
                    ->get(['transaction_id', 'dmc', 'line', 'detail']);
            });

            $measure('execute_test.results_total_count', function () {
                $resultsQuery = DB::table('Transaction_Detail as TD')
                    ->leftJoin('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
                    ->leftJoin('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
                    ->leftJoin('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->whereNull('TD.deleted_at')
                    ->whereNull('TH.deleted_at');

                return (clone $resultsQuery)->count();
            });

            $measure('execute_test.results_page_1', function () {
                return DB::table('Transaction_Detail as TD')
                    ->leftJoin('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
                    ->leftJoin('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
                    ->leftJoin('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->whereNull('TD.deleted_at')
                    ->whereNull('TH.deleted_at')
                    ->select('TD.detail_id', 'TD.transaction_id', 'TD.judgement', 'TD.start_time', 'TD.end_time')
                    ->selectRaw('TM.method_name as method_name')
                    ->selectRaw('IU.name as inspector_name')
                    ->orderByDesc('TD.detail_id')
                    ->limit(20)
                    ->get();
            });

            $measure('report.summary_window', function () use ($windowFrom, $windowTo) {
                return DB::table('Transaction_Header as TH')
                    ->join('Transaction_Detail as TD', 'TD.transaction_id', '=', 'TH.transaction_id')
                    ->whereBetween('TH.receive_date', [$windowFrom, $windowTo])
                    ->whereNull('TD.deleted_at')
                    ->whereNull('TH.deleted_at')
                    ->selectRaw('COUNT(*) as total_rows')
                    ->selectRaw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_count")
                    ->selectRaw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_count")
                    ->first();
            });

            $measure('report.page_1_window', function () use ($windowFrom, $windowTo) {
                return DB::table('Transaction_Header as TH')
                    ->join('Transaction_Detail as TD', 'TD.transaction_id', '=', 'TH.transaction_id')
                    ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
                    ->join('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
                    ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->whereBetween('TH.receive_date', [$windowFrom, $windowTo])
                    ->whereNull('TD.deleted_at')
                    ->whereNull('TH.deleted_at')
                    ->select('TH.transaction_id', 'TH.receive_date', 'TH.dmc', 'TH.line', 'TH.detail', 'TD.judgement')
                    ->orderByDesc('TH.receive_date')
                    ->limit(25)
                    ->get();
            });

            $measure('performance.inspectors_window', function () use ($windowFrom) {
                return DB::table('Transaction_Detail as TD')
                    ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->whereNotNull('TD.start_time')
                    ->whereNotNull('TD.end_time')
                    ->where('TD.end_time', '>=', $windowFrom)
                    ->whereNull('TD.deleted_at')
                    ->select('IU.user_id as id', 'IU.name')
                    ->selectRaw('COUNT(*) as total_tests')
                    ->selectRaw('ROUND(AVG(TD.duration_sec)) as avg_sec')
                    ->selectRaw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_cnt")
                    ->selectRaw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_cnt")
                    ->groupBy('IU.user_id', 'IU.name')
                    ->orderByDesc('total_tests')
                    ->get();
            });

            $measure('performance.details_recent_50', function () use ($windowFrom) {
                $recentDetailIds = DB::table('Transaction_Detail as TD')
                    ->whereNotNull('TD.start_time')
                    ->whereNotNull('TD.end_time')
                    ->where('TD.end_time', '>=', $windowFrom)
                    ->whereNull('TD.deleted_at')
                    ->orderByDesc('TD.end_time')
                    ->limit(50)
                    ->pluck('TD.detail_id');

                if ($recentDetailIds->isEmpty()) {
                    return collect();
                }

                return DB::table('Transaction_Detail as TD')
                    ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
                    ->whereIn('TD.detail_id', $recentDetailIds)
                    ->whereNull('TD.deleted_at')
                    ->whereNull('TH.deleted_at')
                    ->select(
                        'TD.detail_id',
                        'IU.name as inspector',
                        'TH.dmc',
                        'TH.line',
                        'TH.detail',
                        'TD.judgement',
                        'TD.start_time',
                        'TD.end_time',
                        'TD.duration_sec'
                    )
                    ->orderByDesc('TD.end_time')
                    ->get();
            });

            $openJobsCount = (int) (DB::table('Transaction_Header')
                ->whereNull('return_date')
                ->whereNull('deleted_at')
                ->count());

            $failedGates = [];
            foreach ($gateMs as $metricName => $thresholdMs) {
                $measuredMs = (float) ($metrics[$metricName]['ms'] ?? INF);
                $passed = $measuredMs <= $thresholdMs;
                $metrics[$metricName]['gate_ms'] = $thresholdMs;
                $metrics[$metricName]['status'] = $passed ? 'PASS' : 'FAIL';

                if (!$passed) {
                    $failedGates[] = $metricName;
                }
            }

            $reportPayload = [
                'generated_at' => now()->toDateTimeString(),
                'database' => DB::connection()->getDatabaseName(),
                'synthetic' => [
                    'headers' => $headers,
                    'details_per_header' => $detailsPerHeader,
                    'details_total' => $detailRowsTarget,
                    'inserted_headers' => $insertedHeaders,
                    'inserted_details' => $insertedDetails,
                    'open_ratio' => $openRatio,
                    'open_jobs_measured' => $openJobsCount,
                    'seed_ms' => $seedMs,
                    'window_days' => $windowDays,
                    'window_from' => $windowFrom->toDateTimeString(),
                    'window_to' => $windowTo->toDateTimeString(),
                ],
                'metrics' => $metrics,
                'gates' => [
                    'thresholds_ms' => $gateMs,
                    'failed_metrics' => $failedGates,
                    'status' => count($failedGates) === 0 ? 'PASS' : 'FAIL',
                ],
                'rollback' => true,
            ];

            $outputPath = trim((string) $this->option('out'));
            if ($outputPath === '') {
                $outputPath = 'performance/scale-benchmark-' . now()->format('Y-m-d-His') . '.json';
            }

            Storage::disk('local')->put(
                $outputPath,
                json_encode($reportPayload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );

            $this->line('');
            $this->line('Metric                              ms         gate(ms)   status   rows/value');
            $this->line(str_repeat('-', 78));

            foreach ($metrics as $name => $metric) {
                $rowOrValue = $metric['rows'] ?? $metric['value'] ?? '-';
                $gateText = array_key_exists('gate_ms', $metric)
                    ? number_format((float) $metric['gate_ms'], 2)
                    : '-';
                $status = $metric['status'] ?? '-';

                $this->line(
                    str_pad($name, 35)
                    . str_pad(number_format((float) $metric['ms'], 2), 11)
                    . str_pad($gateText, 11)
                    . str_pad($status, 9)
                    . $rowOrValue
                );
            }

            $this->line('');
            $this->line('JSON report: ' . Storage::disk('local')->path($outputPath));
            $this->line('Synthetic data rollback: pending');

            $status = count($failedGates) === 0 ? self::SUCCESS : self::FAILURE;

            if ($status === self::SUCCESS) {
                $this->info('Scale benchmark gate status: PASS');
            } else {
                $this->error('Scale benchmark gate status: FAIL');
            }

            return $status;
        } finally {
            try {
                DB::rollBack();
                $rolledBack = true;
            } catch (\Throwable) {
                $rolledBack = false;
            }

            try {
                $restoreAutoIncrement('Transaction_Header', max($headerMaxBefore + 1, (int) ($headerAutoBefore ?? 1)));
                $restoreAutoIncrement('Transaction_Detail', max($detailMaxBefore + 1, (int) ($detailAutoBefore ?? 1)));
            } catch (\Throwable $e) {
                $this->warn('Could not restore AUTO_INCREMENT values after benchmark: ' . $e->getMessage());
            }

            $this->line($rolledBack
                ? 'Synthetic data rollback: completed.'
                : 'Synthetic data rollback: skipped (transaction may not have been open).');
        }
    }
)->purpose('Run scale-focused query benchmark with synthetic data and PASS/FAIL gates');

Artisan::command(
    'qc:release-gate
    {--headers=50000 : Synthetic header rows for scale benchmark}
    {--details-per-header=4 : Synthetic detail rows per header}
    {--chunk=1000 : Chunk size for synthetic inserts}
    {--window-days=30 : Scale benchmark window}
    {--aggregate-days=120 : Aggregate refresh horizon in days}',
    function () {
        $commands = [
            ['qc:redis-health', ['--fail-on-error' => true]],
            ['qc:aggregate-metrics', ['--days' => (int) $this->option('aggregate-days')]],
            ['qc:warm', []],
            ['qc:scale-benchmark', [
                '--headers' => (int) $this->option('headers'),
                '--details-per-header' => (int) $this->option('details-per-header'),
                '--chunk' => (int) $this->option('chunk'),
                '--window-days' => (int) $this->option('window-days'),
            ]],
        ];

        foreach ($commands as [$name, $params]) {
            $this->line('');
            $this->line('Running: ' . $name);
            $exitCode = Artisan::call($name, $params, $this->output);

            if ($exitCode !== self::SUCCESS) {
                $this->error("Release gate failed at {$name}.");
                return self::FAILURE;
            }
        }

        $this->info('Release gate passed.');

        return self::SUCCESS;
    }
)->purpose('Run Redis health, aggregates, cache warm, and scale benchmark as a release gate');

Artisan::command('qc:profile {--runs=3 : Number of runs per route} {--route=* : Route path(s), e.g. /dashboard}', function () {
    $runs = max(1, (int) $this->option('runs'));
    $routes = $this->option('route');

    if (!is_array($routes) || count($routes) === 0) {
        $routes = ['/dashboard', '/receive-job', '/execute-test', '/report', '/performance'];
    }

    $user = User::query()
        ->where('role', 'admin')
        ->first() ?? User::query()->first();

    if (!$user) {
        $this->error('No user found for profiling. Seed or create at least one user first.');
        return self::FAILURE;
    }

    /** @var HttpKernel $kernel */
    $kernel = app(HttpKernel::class);

    $this->line('Profiling as user: ' . $user->user_name . ' (' . $user->name . ')');
    $this->line('');
    $this->line(str_pad('Route', 18) . str_pad('Avg(ms)', 12) . str_pad('P95(ms)', 12) . str_pad('DB(ms)', 12) . str_pad('Queries', 10));
    $this->line(str_repeat('-', 64));

    foreach ($routes as $uri) {
        $timings = [];
        $dbTimings = [];
        $queryCounts = [];

        for ($i = 0; $i < $runs; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $request = Request::create($uri, 'GET');
            $request->headers->set('X-Inertia', 'true');
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            $request->setLaravelSession(app('session')->driver());
            $request->setUserResolver(fn () => $user);
            Auth::guard('web')->setUser($user);

            $startedAt = microtime(true);
            $response = $kernel->handle($request);
            $elapsedMs = (microtime(true) - $startedAt) * 1000;
            $kernel->terminate($request, $response);
            Auth::guard('web')->logout();

            $queryLog = DB::getQueryLog();
            $dbMs = array_reduce($queryLog, fn ($carry, $entry) => $carry + (float) ($entry['time'] ?? 0), 0.0);

            $timings[] = $elapsedMs;
            $dbTimings[] = $dbMs;
            $queryCounts[] = count($queryLog);
        }

        sort($timings);
        $p95Index = (int) max(0, ceil(0.95 * count($timings)) - 1);
        $avgMs = array_sum($timings) / count($timings);
        $avgDbMs = array_sum($dbTimings) / count($dbTimings);
        $avgQueries = array_sum($queryCounts) / count($queryCounts);

        $this->line(
            str_pad($uri, 18)
            . str_pad(number_format($avgMs, 2), 12)
            . str_pad(number_format($timings[$p95Index], 2), 12)
            . str_pad(number_format($avgDbMs, 2), 12)
            . str_pad(number_format($avgQueries, 1), 10)
        );
    }

    return self::SUCCESS;
})->purpose('Profile key QC routes (response time, DB time, query count)');

Artisan::command('qc:profile-receive-job {--runs=5 : Number of runs per request shape}', function () {
    $runs = max(1, (int) $this->option('runs'));
    $user = User::query()
        ->where('role', 'admin')
        ->first() ?? User::query()->first();

    if (!$user) {
        $this->error('No user found for profiling. Seed or create at least one user first.');
        return self::FAILURE;
    }

    /** @var HttpKernel $kernel */
    $kernel = app(HttpKernel::class);

    $profiles = [
        [
            'label' => 'initial-shell',
            'uri' => '/receive-job',
            'headers' => [
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ],
        [
            'label' => 'history-partial',
            'uri' => '/receive-job',
            'headers' => [
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Partial-Component' => 'ReceiveJob/Create',
                'X-Inertia-Partial-Data' => 'jobs,filters,flash',
            ],
        ],
    ];

    $this->line('Profiling Receive Job as user: ' . $user->user_name . ' (' . $user->name . ')');
    $this->line('');
    $this->line(str_pad('Request', 18) . str_pad('Avg(ms)', 12) . str_pad('P95(ms)', 12) . str_pad('DB(ms)', 12) . str_pad('Queries', 10));
    $this->line(str_repeat('-', 64));

    foreach ($profiles as $profile) {
        $timings = [];
        $dbTimings = [];
        $queryCounts = [];

        for ($i = 0; $i < $runs; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $request = Request::create($profile['uri'], 'GET');

            foreach ($profile['headers'] as $header => $value) {
                $request->headers->set($header, $value);
            }

            $request->setLaravelSession(app('session')->driver());
            $request->setUserResolver(fn () => $user);
            Auth::guard('web')->setUser($user);

            $startedAt = microtime(true);
            $response = $kernel->handle($request);
            $elapsedMs = (microtime(true) - $startedAt) * 1000;
            $kernel->terminate($request, $response);
            Auth::guard('web')->logout();

            $queryLog = DB::getQueryLog();
            $dbMs = array_reduce($queryLog, fn ($carry, $entry) => $carry + (float) ($entry['time'] ?? 0), 0.0);

            $timings[] = $elapsedMs;
            $dbTimings[] = $dbMs;
            $queryCounts[] = count($queryLog);
        }

        sort($timings);
        $p95Index = (int) max(0, ceil(0.95 * count($timings)) - 1);
        $avgMs = array_sum($timings) / count($timings);
        $avgDbMs = array_sum($dbTimings) / count($dbTimings);
        $avgQueries = array_sum($queryCounts) / count($queryCounts);

        $this->line(
            str_pad($profile['label'], 18)
            . str_pad(number_format($avgMs, 2), 12)
            . str_pad(number_format($timings[$p95Index], 2), 12)
            . str_pad(number_format($avgDbMs, 2), 12)
            . str_pad(number_format($avgQueries, 1), 10)
        );
    }

    return self::SUCCESS;
})->purpose('Profile Receive Job initial shell versus partial history reload');

Artisan::command('qc:warm', function (DashboardMetricsService $metricsService) {
    $periods = ['today', 'week', 'month', '30days', 'quarter'];

    foreach ($periods as $period) {
        [$from, $to] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'quarter' => [now()->startOfQuarter(), now()->endOfDay()],
        };

        DashboardCache::store()->remember(DashboardCache::summaryKey($period), now()->addMinutes(10), fn () => [
            'currentPeriod' => $period,
            'metrics' => $metricsService->getOverviewMetrics($from, $to),
        ]);

        DashboardCache::store()->remember(DashboardCache::primaryKey($period), now()->addMinutes(10), fn () => [
            'weeklyData' => $metricsService->getWeeklyTrend(),
            'equipRank' => $metricsService->getEquipmentRanking(5, $from, $to),
            'failByEquip' => $metricsService->getFailuresByEquipment(5, $from, $to),
            'inspectorData' => $metricsService->getInspectorData(5, $from, $to),
        ]);

        DashboardCache::store()->remember(DashboardCache::secondaryKey($period), now()->addMinutes(10), fn () => [
            'dailyData' => $metricsService->getDailyTrend(),
            'monthlyData' => $metricsService->getMonthlyTrend(),
            'inspectorEff' => $metricsService->getInspectorEfficiency(5, $from, $to),
            'recentActivities' => $metricsService->getRecentActivities(5, $from, $to),
        ]);

        DashboardCache::store()->remember(DashboardCache::pageKey($period), now()->addMinutes(10), fn () => [
            'currentPeriod' => $period,
            'metrics' => $metricsService->getOverviewMetrics($from, $to),
            'weeklyData' => $metricsService->getWeeklyTrend(),
        ]);

        DashboardCache::store()->remember(
            DashboardCache::simpleDailyKey($period),
            now()->addMinutes(10),
            fn () => $metricsService->getDailyTrend()
        );

        DashboardCache::store()->remember(
            DashboardCache::simpleMonthlyKey($period),
            now()->addMinutes(10),
            fn () => $metricsService->getMonthlyTrend()
        );

        DashboardCache::store()->remember(
            DashboardCache::simpleInspectorsKey($period),
            now()->addMinutes(10),
            fn () => $metricsService->getInspectorData(5, $from, $to)->toArray()
        );
    }

    Cache::remember('receive_job.externals', now()->addMinutes(10), fn () => \App\Models\ExternalUser::query()
        ->when(\App\Support\SchemaCapabilities::hasColumn('External_Users', 'is_active'), fn ($query) => $query->where('is_active', true))
        ->orderBy('external_name')
        ->get(['external_id', 'external_name']));
    Cache::remember('receive_job.internals', now()->addMinutes(10), fn () => \App\Models\User::orderBy('name')->get(['user_id', 'name']));
    ReceiveJobController::warmDefaultHistoryCache();
    Cache::remember('execute_test.methods', now()->addMinutes(10), fn () => \App\Models\TestMethod::query()
        ->when(\App\Support\SchemaCapabilities::hasColumn('Test_Methods', 'is_active'), fn ($query) => $query->where('is_active', true))
        ->orderBy('method_name')
        ->get());
    Cache::remember('execute_test.inspectors', now()->addMinutes(10), fn () => \App\Models\User::orderBy('name')->get(['user_id', 'name']));
    Cache::remember('execute_test.pending_jobs.active', now()->addSeconds(30), function () {
        return \App\Models\TransactionHeader::whereNull('return_date')
            ->orderByDesc('receive_date')
            ->limit(500)
            ->get(['transaction_id', 'dmc', 'line', 'detail'])
            ->map(fn ($job) => [
                'transaction_id' => $job->transaction_id,
                'dmc' => $job->dmc,
                'line' => $job->line,
                'detail' => $job->detail,
            ]);
    });
    Cache::remember('execute_test.pending_jobs_count.active', now()->addSeconds(30), fn () => \App\Models\TransactionHeader::whereNull('return_date')->count());
    Cache::remember('performance.inspectors.30d', now()->addMinutes(3), function () {
        $windowStart = now()->subDays(30);
        $windowStartDate = $windowStart->toDateString();
        $windowEndDate = now()->toDateString();
        $connection = \App\Support\ReportingConnection::connection();

        if (\App\Support\SchemaCapabilities::hasTable('performance_daily_inspector_aggregates')) {
            return $connection->table('performance_daily_inspector_aggregates as PIA')
                ->join('Internal_Users as IU', 'PIA.internal_id', '=', 'IU.user_id')
                ->whereBetween('PIA.date_key', [$windowStartDate, $windowEndDate])
                ->select(
                    'IU.user_id as id',
                    'IU.name',
                    DB::raw('SUM(PIA.total_tests) as total_tests'),
                    DB::raw('ROUND(SUM(PIA.duration_total_sec) / NULLIF(SUM(PIA.duration_samples), 0)) as avg_sec'),
                    DB::raw('MIN(PIA.min_duration_sec) as min_sec'),
                    DB::raw('MAX(PIA.max_duration_sec) as max_sec'),
                    DB::raw('SUM(PIA.ok_count) as ok_cnt'),
                    DB::raw('SUM(PIA.ng_count) as ng_cnt')
                )
                ->groupBy('IU.user_id', 'IU.name')
                ->orderByDesc('total_tests')
                ->get();
        }

        return $connection->table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.end_time', '>=', $windowStart)
            ->when(\App\Support\SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at'), fn ($query) => $query->whereNull('TD.deleted_at'))
            ->select(
                'IU.user_id as id',
                'IU.name',
                DB::raw('COUNT(*) as total_tests'),
                DB::raw('ROUND(AVG(TD.duration_sec)) as avg_sec'),
                DB::raw('MIN(TD.duration_sec) as min_sec'),
                DB::raw('MAX(TD.duration_sec) as max_sec'),
                DB::raw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_cnt"),
                DB::raw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_cnt")
            )
            ->groupBy('IU.user_id', 'IU.name')
            ->orderByDesc('total_tests')
            ->get();
    });

    Cache::remember('performance.details.30d.recent50', now()->addMinutes(3), function () {
        $windowStart = now()->subDays(30);
        $hasDetailDeletedAt = \App\Support\SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
        $hasHeaderDeletedAt = \App\Support\SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $connection = \App\Support\ReportingConnection::connection();

        $recentDetailIds = $connection->table('Transaction_Detail as TD')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.end_time', '>=', $windowStart)
            ->when($hasDetailDeletedAt, fn ($query) => $query->whereNull('TD.deleted_at'))
            ->orderByDesc('TD.end_time')
            ->limit(50)
            ->pluck('TD.detail_id');

        if ($recentDetailIds->isEmpty()) {
            return collect();
        }

        return $connection->table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->whereIn('TD.detail_id', $recentDetailIds)
            ->when($hasDetailDeletedAt, fn ($query) => $query->whereNull('TD.deleted_at'))
            ->when($hasHeaderDeletedAt, fn ($query) => $query->whereNull('TH.deleted_at'))
            ->select(
                'TD.detail_id',
                'IU.name as inspector',
                'TH.dmc',
                'TH.line',
                'TH.detail',
                'TD.judgement',
                'TD.start_time',
                'TD.end_time',
                'TD.duration_sec'
            )
            ->orderByDesc('TD.end_time')
            ->get();
    });

    $reportFilters = [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
        'dmc' => '',
        'per_page' => 25,
    ];
    $reportPageFilters = [...$reportFilters, 'page' => 1];
    $reportConnection = \App\Support\ReportingConnection::connection();
    $hasDetailDeletedAt = \App\Support\SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
    $hasHeaderDeletedAt = \App\Support\SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
    $fromDate = now()->startOfMonth()->toDateString();
    $toDate = now()->toDateString();
    $fromDateTime = now()->startOfMonth()->startOfDay();
    $toDateTime = now()->endOfDay();

    Cache::remember(ReportCacheKey::make('summary', $reportFilters), now()->addMinutes(3), function () use ($reportConnection, $reportFilters, $fromDate, $toDate, $fromDateTime, $toDateTime, $hasDetailDeletedAt, $hasHeaderDeletedAt) {
        if (\App\Support\SchemaCapabilities::hasTable('report_daily_aggregates')) {
            $summary = $reportConnection->table('report_daily_aggregates')
                ->whereBetween('date_key', [$fromDate, $toDate])
                ->selectRaw('SUM(total_rows) as total_rows')
                ->selectRaw('SUM(ok_count) as ok_count')
                ->selectRaw('SUM(ng_count) as ng_count')
                ->first();

            return [
                'total_rows' => (int) ($summary->total_rows ?? 0),
                'ok_count' => (int) ($summary->ok_count ?? 0),
                'ng_count' => (int) ($summary->ng_count ?? 0),
            ];
        }

        $query = $reportConnection->table('Transaction_Header as TH')
            ->join('Transaction_Detail as TD', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->whereBetween('TH.receive_date', [$fromDateTime, $toDateTime]);

        if ($hasDetailDeletedAt) {
            $query->whereNull('TD.deleted_at');
        }
        if ($hasHeaderDeletedAt) {
            $query->whereNull('TH.deleted_at');
        }

        $summary = $query
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_count")
            ->selectRaw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_count")
            ->first();

        return [
            'total_rows' => (int) ($summary->total_rows ?? 0),
            'ok_count' => (int) ($summary->ok_count ?? 0),
            'ng_count' => (int) ($summary->ng_count ?? 0),
        ];
    });

    Cache::remember(ReportCacheKey::make('page', $reportPageFilters), now()->addMinutes(3), function () use ($reportConnection, $fromDateTime, $toDateTime, $hasDetailDeletedAt, $hasHeaderDeletedAt, $reportFilters) {
        $query = $reportConnection->table('Transaction_Header as TH')
            ->join('Transaction_Detail as TD', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
            ->join('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereBetween('TH.receive_date', [$fromDateTime, $toDateTime]);

        if ($hasDetailDeletedAt) {
            $query->whereNull('TD.deleted_at');
        }
        if ($hasHeaderDeletedAt) {
            $query->whereNull('TH.deleted_at');
        }

        return $query
            ->select(
                'TH.transaction_id',
                'TH.dmc',
                'TH.line',
                'TH.receive_date',
                'EU.external_name as sender',
                'TH.detail',
                'TM.method_name',
                'IU.name as inspector',
                'TD.start_time',
                'TD.end_time',
                'TD.judgement',
                'TD.remark'
            )
            ->orderByDesc('TH.receive_date')
            ->forPage(1, (int) $reportFilters['per_page'])
            ->get();
    });

    $this->info('Warmed dashboard, workflow, and performance caches.');

    return self::SUCCESS;
})->purpose('Warm the hot-path caches used by dashboard, workflow, and performance pages');

Schedule::command('qc:baseline --pretty')
    ->weeklyOn(1, '02:00')
    ->name('qc-weekly-baseline');

Schedule::command('qc:aggregate-metrics --days=120')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->name('qc-aggregate-refresh');

Schedule::command('qc:warm')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->name('qc-hot-cache-warm');

Schedule::command('qc:redis-health')
    ->everyMinute()
    ->withoutOverlapping()
    ->name('qc-redis-health');

Schedule::command('qc:partition-aggregates --years=3 --execute')
    ->monthlyOn(1, '01:30')
    ->withoutOverlapping()
    ->name('qc-partition-aggregates');
