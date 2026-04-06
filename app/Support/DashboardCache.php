<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository;
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

    public static function pageKey(string $period): string
    {
        return "dashboard.page.{$period}";
    }

    public static function simpleDailyKey(string $period): string
    {
        return "dashboard.simple.daily.{$period}";
    }

    public static function simpleMonthlyKey(string $period): string
    {
        return "dashboard.simple.monthly.{$period}";
    }

    public static function simpleInspectorsKey(string $period): string
    {
        return "dashboard.simple.inspectors.{$period}";
    }

    public static function store(): Repository
    {
        $store = (string) config('cache.default', 'file');

        if ($store === 'failover') {
            $store = 'file';
        }

        return Cache::store($store);
    }

    public static function flush(): void
    {
        foreach (self::PERIODS as $period) {
            self::store()->forget(self::summaryKey($period));
            self::store()->forget(self::primaryKey($period));
            self::store()->forget(self::secondaryKey($period));
            self::store()->forget(self::pageKey($period));
            self::store()->forget(self::simpleDailyKey($period));
            self::store()->forget(self::simpleMonthlyKey($period));
            self::store()->forget(self::simpleInspectorsKey($period));
        }
    }
}
