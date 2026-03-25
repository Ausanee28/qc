<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    private const PERIODS = ['today', 'week', 'month', '30days', 'quarter'];

    public static function summaryKey(string $period): string
    {
        return "dashboard.summary.{$period}";
    }

    public static function primaryKey(string $period): string
    {
        return "dashboard.primary.{$period}";
    }

    public static function secondaryKey(string $period): string
    {
        return "dashboard.secondary.{$period}";
    }

    public static function flush(): void
    {
        foreach (self::PERIODS as $period) {
            Cache::forget(self::summaryKey($period));
            Cache::forget(self::primaryKey($period));
            Cache::forget(self::secondaryKey($period));
        }
    }
}
