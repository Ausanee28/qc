<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class ReportingConnection
{
    public static function name(): string
    {
        $configured = (string) config('database.reporting_connection', config('database.default'));
        $connections = (array) config('database.connections', []);

        if (array_key_exists($configured, $connections)) {
            return $configured;
        }

        return (string) config('database.default');
    }

    public static function connection(): Connection
    {
        return DB::connection(self::name());
    }
}

