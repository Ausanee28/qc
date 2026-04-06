<?php

use App\Services\DashboardMetricsService;
use App\Http\Controllers\ReceiveJobController;
use App\Support\DashboardCache;
use App\Support\PerformanceBaselineCollector;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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

    Cache::remember('receive_job.externals', now()->addMinutes(10), fn () => \App\Models\ExternalUser::orderBy('external_name')->get(['external_id', 'external_name']));
    Cache::remember('receive_job.internals', now()->addMinutes(10), fn () => \App\Models\User::orderBy('name')->get(['user_id', 'name']));
    ReceiveJobController::warmDefaultHistoryCache();
    Cache::remember('execute_test.methods', now()->addMinutes(10), fn () => \App\Models\TestMethod::orderBy('method_name')->get());
    Cache::remember('execute_test.inspectors', now()->addMinutes(10), fn () => \App\Models\User::orderBy('name')->get(['user_id', 'name']));
    Cache::remember('execute_test.pending_jobs.active', now()->addSeconds(30), function () {
        return \App\Models\TransactionHeader::whereNull('return_date')
            ->orderByDesc('receive_date')
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

        return DB::table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.start_time', '>=', $windowStart)
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

        return DB::table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.start_time', '>=', $windowStart)
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
            ->limit(50)
            ->get();
    });

    $this->info('Warmed dashboard, workflow, and performance caches.');

    return self::SUCCESS;
})->purpose('Warm the hot-path caches used by dashboard, workflow, and performance pages');

Schedule::command('qc:baseline --pretty')
    ->weeklyOn(1, '02:00')
    ->name('qc-weekly-baseline');

Schedule::command('qc:warm')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->name('qc-hot-cache-warm');
