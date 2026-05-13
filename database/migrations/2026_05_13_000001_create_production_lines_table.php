<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Production_Lines', function (Blueprint $table) {
            $table->increments('line_id');
            $table->string('line_name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->index(['is_active', 'sort_order', 'line_name'], 'idx_production_lines_active_order_name');
        });

        $defaultLines = [
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

        DB::table('Production_Lines')->insert(array_map(
            fn (string $lineName, int $index): array => [
                'line_name' => $lineName,
                'sort_order' => $index + 1,
                'is_active' => true,
            ],
            $defaultLines,
            array_keys($defaultLines)
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('Production_Lines');
    }
};
