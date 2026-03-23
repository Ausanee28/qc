<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PendingJobsVersion
{
    private const CACHE_KEY = 'execute_test.pending_jobs.version';

    public static function current(): int
    {
        return (int) Cache::rememberForever(self::CACHE_KEY, fn () => 1);
    }

    public static function bump(): int
    {
        $nextVersion = self::current() + 1;
        Cache::forever(self::CACHE_KEY, $nextVersion);

        return $nextVersion;
    }
}
