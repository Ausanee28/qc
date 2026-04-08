<?php

namespace App\Support;

class ReportCacheKey
{
    public static function make(string $segment, array $payload): string
    {
        $version = ReportCacheVersion::current();
        $hash = md5(json_encode($payload));

        return "report.{$version}.{$segment}.{$hash}";
    }
}

