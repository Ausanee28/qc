<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestPerformanceProfiler
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('performance.profiler_enabled', true)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $dbTimeMs = 0.0;
        $dbQueryCount = 0;

        DB::listen(function ($query) use (&$dbTimeMs, &$dbQueryCount): void {
            $dbQueryCount++;
            $dbTimeMs += (float) ($query->time ?? 0.0);
        });

        /** @var Response $response */
        $response = $next($request);

        $totalMs = (microtime(true) - $startedAt) * 1000;
        $appMs = max($totalMs - $dbTimeMs, 0.0);

        if (config('performance.response_headers', true)) {
            $response->headers->set('Server-Timing', sprintf('app;dur=%.1f, db;dur=%.1f', $appMs, $dbTimeMs));
            $response->headers->set('X-Response-Time-Ms', (string) round($totalMs, 2));
            $response->headers->set('X-DB-Time-Ms', (string) round($dbTimeMs, 2));
            $response->headers->set('X-DB-Queries', (string) $dbQueryCount);
        }

        if (config('performance.log_slow_requests', true) && $totalMs >= (float) config('performance.slow_request_ms', 300)) {
            $logContext = [
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'response_ms' => round($totalMs, 2),
                'app_ms' => round($appMs, 2),
                'db_ms' => round($dbTimeMs, 2),
                'db_queries' => $dbQueryCount,
            ];

            $channel = config('performance.log_channel');

            if (is_string($channel) && $channel !== '') {
                Log::channel($channel)->warning('Slow request detected', $logContext);
            } else {
                Log::warning('Slow request detected', $logContext);
            }
        }

        return $response;
    }
}
