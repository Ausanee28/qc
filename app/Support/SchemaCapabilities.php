<?php

namespace App\Support;

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

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (array_key_exists($key, self::$columnExistsCache)) {
            return self::$columnExistsCache[$key];
        }

        try {
            self::$columnExistsCache[$key] = Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            self::$columnExistsCache[$key] = false;
        }

        return self::$columnExistsCache[$key];
    }
}

