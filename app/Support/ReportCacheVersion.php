<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ReportCacheVersion
{
    private const KEY = 'report.cache.version';

    public static function current(): string
    {
        return (string) Cache::rememberForever(self::KEY, static fn () => 'v1');
    }

    public static function bump(): string
    {
        $version = 'v' . now()->format('YmdHis');
        Cache::forever(self::KEY, $version);

        return $version;
    }
}

