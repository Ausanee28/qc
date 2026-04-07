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

    private static function persistentCacheKey(string $table, string $column): string
    {
        $connection = DB::connection();
        $connectionName = (string) $connection->getName();
        $databaseName = (string) ($connection->getDatabaseName() ?? 'default');

        return "schema.capability.{$connectionName}.{$databaseName}.{$table}.{$column}";
    }
}
