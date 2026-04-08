<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaCapabilities
{
    /**
     * Cache column-existence checks per request/process to avoid repeated
     * information_schema lookups on hot paths.
     *
     * @var array<string, bool>
     */
    private static array $columnExistsCache = [];
    private static array $tableExistsCache = [];
    private const CACHE_TTL_SECONDS = 900;

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (array_key_exists($key, self::$columnExistsCache)) {
            return self::$columnExistsCache[$key];
        }

        $persistentKey = self::persistentCacheKey($table, $column);

        try {
            self::$columnExistsCache[$key] = (bool) Cache::remember(
                $persistentKey,
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                static fn () => Schema::hasColumn($table, $column)
            );
        } catch (\Throwable) {
            self::$columnExistsCache[$key] = false;
        }

        return self::$columnExistsCache[$key];
    }

    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tableExistsCache)) {
            return self::$tableExistsCache[$table];
        }

        $persistentKey = self::persistentTableCacheKey($table);

        try {
            self::$tableExistsCache[$table] = (bool) Cache::remember(
                $persistentKey,
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                static fn () => Schema::hasTable($table)
            );
        } catch (\Throwable) {
            self::$tableExistsCache[$table] = false;
        }

        return self::$tableExistsCache[$table];
    }

    private static function persistentCacheKey(string $table, string $column): string
    {
        $connection = DB::connection();
        $connectionName = (string) $connection->getName();
        $databaseName = (string) ($connection->getDatabaseName() ?? 'default');

        return "schema.capability.{$connectionName}.{$databaseName}.{$table}.{$column}";
    }

    private static function persistentTableCacheKey(string $table): string
    {
        $connection = DB::connection();
        $connectionName = (string) $connection->getName();
        $databaseName = (string) ($connection->getDatabaseName() ?? 'default');

        return "schema.table.{$connectionName}.{$databaseName}.{$table}";
    }
}
