<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    public const DEFAULT_LINE_NAMES = [
        'Line 1',
        'Line 2',
        'Line 3',
        'Line 4',
        'Line 5',
        'Line 6',
        'Line 7',
        'Line 8',
        'Line 9',
        'Line 10',
        'Line 11',
        'P4#1',
        'P4#2',
        'P4#3',
        'MTA 1',
        'MTA 2',
        'ITT',
    ];

    protected $table = 'Production_Lines';
    protected $primaryKey = 'line_id';
    public $timestamps = false;
    protected $fillable = ['line_name', 'sort_order', 'is_active'];
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function defaultOptions(): array
    {
        return array_map(
            fn (string $lineName, int $index): array => [
                'line_id' => $index + 1,
                'line_name' => $lineName,
            ],
            self::DEFAULT_LINE_NAMES,
            array_keys(self::DEFAULT_LINE_NAMES)
        );
    }
}
