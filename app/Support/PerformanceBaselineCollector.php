<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PerformanceBaselineCollector
{
    public function collect(): array
    {
        $tables = DB::select("
            SELECT
                table_name,
                table_rows,
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");

        $indexes = DB::select("
            SELECT
                table_name,
                index_name,
                seq_in_index,
                column_name,
                non_unique
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name IN ('Transaction_Header', 'Transaction_Detail', 'Internal_Users', 'Test_Methods', 'External_Users')
            ORDER BY table_name, index_name, seq_in_index
        ");

        $from = now()->startOfMonth()->format('Y-m-d H:i:s');
        $to = now()->endOfDay()->format('Y-m-d H:i:s');

        $headerRangeExplain = DB::select("
            EXPLAIN
            SELECT transaction_id
            FROM Transaction_Header
            WHERE deleted_at IS NULL
              AND receive_date BETWEEN ? AND ?
        ", [$from, $to]);

        $detailJudgementExplain = DB::select("
            EXPLAIN
            SELECT COUNT(*)
            FROM Transaction_Detail
            WHERE deleted_at IS NULL
              AND transaction_id IN (
                  SELECT transaction_id
                  FROM Transaction_Header
                  WHERE deleted_at IS NULL
                    AND receive_date BETWEEN ? AND ?
              )
              AND judgement = 'OK'
        ", [$from, $to]);

        $inspectorEfficiencyExplain = DB::select("
            EXPLAIN
            SELECT IU.name, AVG(TIMESTAMPDIFF(SECOND, TD.start_time, TD.end_time)) AS avg_seconds
            FROM Transaction_Detail TD
            JOIN Internal_Users IU ON TD.internal_id = IU.user_id
            JOIN Transaction_Header TH ON TD.transaction_id = TH.transaction_id
            WHERE TD.deleted_at IS NULL
              AND TH.deleted_at IS NULL
              AND TH.receive_date BETWEEN ? AND ?
              AND TD.internal_id IS NOT NULL
              AND TD.start_time IS NOT NULL
              AND TD.end_time IS NOT NULL
            GROUP BY TD.internal_id, IU.name
            ORDER BY avg_seconds DESC
            LIMIT 5
        ", [$from, $to]);

        return [
            'generated_at' => now()->toDateTimeString(),
            'database' => DB::connection()->getDatabaseName(),
            'window' => ['from' => $from, 'to' => $to],
            'table_size_rows' => $tables,
            'indexes' => $indexes,
            'explain' => [
                'header_range' => $headerRangeExplain,
                'detail_by_header_and_judgement' => $detailJudgementExplain,
                'inspector_efficiency' => $inspectorEfficiencyExplain,
            ],
        ];
    }
}

