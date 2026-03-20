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
    'profiler_enabled' => env('PERF_PROFILER_ENABLED', true),

    'response_headers' => env('PERF_RESPONSE_HEADERS', true),

    'log_slow_requests' => env('PERF_LOG_SLOW_REQUESTS', true),

    'slow_request_ms' => (float) env('PERF_SLOW_REQUEST_MS', 300),

    'log_channel' => env('PERF_LOG_CHANNEL'),
];

