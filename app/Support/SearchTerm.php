<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SearchTerm
{
    public static function tokens(string $search): array
    {
        $tokens = preg_split('/\s+/u', trim($search)) ?: [];

        return array_values(array_filter($tokens, fn (string $token) => $token !== ''));
    }

    public static function applyTokenizedLike($query, array $columns, string $search): void
    {
        foreach (self::tokens($search) as $token) {
            $query->where(function ($tokenQuery) use ($columns, $token): void {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $tokenQuery->{$method}($column, 'like', '%' . self::escapeLike($token) . '%');
                }
            });
        }
    }

    public static function canUseFullText(string $search): bool
    {
        $value = trim($search);

        if (mb_strlen($value) < 3) {
            return false;
        }

        $driver = DB::connection()->getDriverName();

        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    public static function toBooleanTerm(string $search): string
    {
        $tokens = preg_split('/\s+/u', trim($search)) ?: [];
        $terms = [];

        foreach ($tokens as $token) {
            $normalized = preg_replace('/[^\p{L}\p{N}_-]+/u', '', $token) ?? '';

            if (mb_strlen($normalized) < 2) {
                continue;
            }

            $terms[] = '+' . $normalized . '*';
        }

        return implode(' ', $terms);
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
