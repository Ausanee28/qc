<?php

use App\Support\PerformanceBaselineCollector;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

Schedule::command('qc:baseline --pretty')
    ->weeklyOn(1, '02:00')
    ->name('qc-weekly-baseline');
