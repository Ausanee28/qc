<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PendingJobsVersion
{
    private const CACHE_KEY = 'execute_test.pending_jobs_version';

    public static function current(): string
    {
        return (string) Cache::rememberForever(self::CACHE_KEY, function () {
            return now()->format('Uv');
        });
    }

    public static function bump(): string
    {
        $token = now()->format('Uv');
        Cache::forever(self::CACHE_KEY, $token);

        return $token;
    }
}
