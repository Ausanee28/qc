<?php

namespace App\Services;

use App\Support\SchemaCapabilities;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateMetricsService
{
    public function refresh(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $dailyRows = $this->refreshReportDaily($from, $to);
        $monthlyRows = $this->refreshReportMonthly($from, $to);
        $inspectorRows = $this->refreshPerformanceDailyInspectors($from, $to);

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'report_daily_rows' => $dailyRows,
            'report_monthly_rows' => $monthlyRows,
            'performance_daily_inspector_rows' => $inspectorRows,
        ];
    }

    private function refreshReportDaily(Carbon $from, Carbon $to): int
    {
        DB::table('report_daily_aggregates')
            ->whereBetween('date_key', [$from->toDateString(), $to->toDateString()])
            ->delete();

        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');

        $whereParts = ['TH.receive_date BETWEEN ? AND ?'];
        if ($hasHeaderDeletedAt) {
            $whereParts[] = 'TH.deleted_at IS NULL';
        }
        if ($hasDetailDeletedAt) {
            $whereParts[] = 'TD.deleted_at IS NULL';
        }

        $dateExpr = $this->dateExpression('TH.receive_date');
        $monthExpr = $this->monthExpression('TH.receive_date');
        $normalizedDmcExpr = "COALESCE(NULLIF(TRIM(TH.dmc), ''), '')";
        $whereSql = implode(' AND ', $whereParts);

        $sql = "
            INSERT INTO report_daily_aggregates (
                date_key,
                month_key,
                dmc,
                total_rows,
                ok_count,
                ng_count,
                aggregated_at
            )
            SELECT
                {$dateExpr} as date_key,
                {$monthExpr} as month_key,
                {$normalizedDmcExpr} as dmc,
                COUNT(*) as total_rows,
                SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_count,
                SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_count,
                NOW() as aggregated_at
            FROM Transaction_Header TH
            INNER JOIN Transaction_Detail TD ON TD.transaction_id = TH.transaction_id
            WHERE {$whereSql}
            GROUP BY {$dateExpr}, {$monthExpr}, TH.dmc
        ";

        return DB::affectingStatement($sql, [$from->toDateTimeString(), $to->toDateTimeString()]);
    }

    private function refreshReportMonthly(Carbon $from, Carbon $to): int
    {
        $fromMonth = $from->format('Y-m');
        $toMonth = $to->format('Y-m');

        DB::table('report_monthly_aggregates')
            ->whereBetween('month_key', [$fromMonth, $toMonth])
            ->delete();

        $sql = "
            INSERT INTO report_monthly_aggregates (
                month_key,
                dmc,
                total_rows,
                ok_count,
                ng_count,
                aggregated_at
            )
            SELECT
                month_key,
                dmc,
                SUM(total_rows) as total_rows,
                SUM(ok_count) as ok_count,
                SUM(ng_count) as ng_count,
                NOW() as aggregated_at
            FROM report_daily_aggregates
            WHERE date_key BETWEEN ? AND ?
            GROUP BY month_key, dmc
        ";

        return DB::affectingStatement($sql, [$from->toDateString(), $to->toDateString()]);
    }

    private function refreshPerformanceDailyInspectors(Carbon $from, Carbon $to): int
    {
        DB::table('performance_daily_inspector_aggregates')
            ->whereBetween('date_key', [$from->toDateString(), $to->toDateString()])
            ->delete();

        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');

        $whereParts = [
            'TD.end_time BETWEEN ? AND ?',
            'TD.internal_id IS NOT NULL',
            'TD.start_time IS NOT NULL',
            'TD.end_time IS NOT NULL',
        ];

        if ($hasHeaderDeletedAt) {
            $whereParts[] = 'TH.deleted_at IS NULL';
        }

        if ($hasDetailDeletedAt) {
            $whereParts[] = 'TD.deleted_at IS NULL';
        }

        $dateExpr = $this->dateExpression('TD.end_time');
        $monthExpr = $this->monthExpression('TD.end_time');
        $whereSql = implode(' AND ', $whereParts);

        $sql = "
            INSERT INTO performance_daily_inspector_aggregates (
                date_key,
                month_key,
                internal_id,
                total_tests,
                ok_count,
                ng_count,
                duration_total_sec,
                duration_samples,
                min_duration_sec,
                max_duration_sec,
                aggregated_at
            )
            SELECT
                {$dateExpr} as date_key,
                {$monthExpr} as month_key,
                TD.internal_id,
                COUNT(*) as total_tests,
                SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_count,
                SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_count,
                SUM(CASE WHEN TD.duration_sec IS NOT NULL THEN TD.duration_sec ELSE 0 END) as duration_total_sec,
                SUM(CASE WHEN TD.duration_sec IS NOT NULL THEN 1 ELSE 0 END) as duration_samples,
                MIN(TD.duration_sec) as min_duration_sec,
                MAX(TD.duration_sec) as max_duration_sec,
                NOW() as aggregated_at
            FROM Transaction_Detail TD
            INNER JOIN Transaction_Header TH ON TH.transaction_id = TD.transaction_id
            WHERE {$whereSql}
            GROUP BY {$dateExpr}, {$monthExpr}, TD.internal_id
        ";

        return DB::affectingStatement($sql, [$from->toDateTimeString(), $to->toDateTimeString()]);
    }

    private function dateExpression(string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "strftime('%Y-%m-%d', {$column})";
        }

        return "DATE({$column})";
    }

    private function monthExpression(string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "strftime('%Y-%m', {$column})";
        }

        return "DATE_FORMAT({$column}, '%Y-%m')";
    }
}
