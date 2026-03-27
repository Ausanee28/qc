<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lightweight Request Profiler
    |--------------------------------------------------------------------------
    |
    | Toggle request timing instrumentation, response timing headers, and
    | slow-request logging for quick performance diagnostics in local/dev.
    |
    */
    'profiler_enabled' => env('PERF_PROFILER_ENABLED', env('APP_DEBUG', false)),

    'response_headers' => env('PERF_RESPONSE_HEADERS', env('APP_DEBUG', false)),

    'log_slow_requests' => env('PERF_LOG_SLOW_REQUESTS', env('APP_DEBUG', false)),

    'slow_request_ms' => (float) env('PERF_SLOW_REQUEST_MS', 300),

    'log_channel' => env('PERF_LOG_CHANNEL'),
];
