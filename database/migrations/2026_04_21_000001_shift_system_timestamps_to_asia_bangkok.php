<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->shiftTransactionHeaderColumn('receive_date', 7);
        $this->shiftTransactionHeaderColumn('return_date', 7);
        $this->shiftTransactionHeaderColumn('deleted_at', 7);
        $this->shiftTransactionDetailColumn('deleted_at', 7);
    }

    public function down(): void
    {
        $this->shiftTransactionHeaderColumn('receive_date', -7);
        $this->shiftTransactionHeaderColumn('return_date', -7);
        $this->shiftTransactionHeaderColumn('deleted_at', -7);
        $this->shiftTransactionDetailColumn('deleted_at', -7);
    }

    private function shiftTransactionHeaderColumn(string $column, int $hours): void
    {
        if (!Schema::hasColumn('Transaction_Header', $column)) {
            return;
        }

        $this->shiftDateTimeColumn('Transaction_Header', $column, $hours);
    }

    private function shiftTransactionDetailColumn(string $column, int $hours): void
    {
        if (!Schema::hasColumn('Transaction_Detail', $column)) {
            return;
        }

        $this->shiftDateTimeColumn('Transaction_Detail', $column, $hours);
    }

    private function shiftDateTimeColumn(string $table, string $column, int $hours): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            return;
        }

        $quotedTable = $connection->getQueryGrammar()->wrapTable($table);
        $quotedColumn = $connection->getQueryGrammar()->wrap($column);

        if ($driver === 'sqlite') {
            $modifier = sprintf('%+d hours', $hours);

            DB::statement(
                "UPDATE {$quotedTable}
                SET {$quotedColumn} = datetime({$quotedColumn}, ?)
                WHERE {$quotedColumn} IS NOT NULL",
                [$modifier]
            );

            return;
        }

        $absoluteHours = abs($hours);
        $function = $hours >= 0 ? 'DATE_ADD' : 'DATE_SUB';

        DB::statement(
            "UPDATE {$quotedTable}
            SET {$quotedColumn} = {$function}({$quotedColumn}, INTERVAL {$absoluteHours} HOUR)
            WHERE {$quotedColumn} IS NOT NULL"
        );
    }
};
