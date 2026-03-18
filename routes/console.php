<?php

use App\Support\PerformanceBaselineCollector;
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

Schedule::command('qc:baseline --pretty')
    ->weeklyOn(1, '02:00')
    ->name('qc-weekly-baseline');
