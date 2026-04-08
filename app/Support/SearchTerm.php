<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SearchTerm
{
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
}
