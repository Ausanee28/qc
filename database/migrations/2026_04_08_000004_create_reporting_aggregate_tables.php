<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_daily_aggregates', function (Blueprint $table) {
            $table->date('date_key');
            $table->string('month_key', 7);
            $table->string('dmc', 255)->default('');
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('ok_count')->default(0);
            $table->unsignedBigInteger('ng_count')->default(0);
            $table->timestamp('aggregated_at')->nullable();

            $table->primary(['date_key', 'dmc'], 'pk_report_daily_aggregates');
            $table->index(['month_key', 'dmc'], 'idx_rda_month_dmc');
        });

        Schema::create('report_monthly_aggregates', function (Blueprint $table) {
            $table->string('month_key', 7);
            $table->string('dmc', 255)->default('');
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('ok_count')->default(0);
            $table->unsignedBigInteger('ng_count')->default(0);
            $table->timestamp('aggregated_at')->nullable();

            $table->primary(['month_key', 'dmc'], 'pk_report_monthly_aggregates');
        });

        Schema::create('performance_daily_inspector_aggregates', function (Blueprint $table) {
            $table->date('date_key');
            $table->string('month_key', 7);
            $table->unsignedInteger('internal_id');
            $table->unsignedBigInteger('total_tests')->default(0);
            $table->unsignedBigInteger('ok_count')->default(0);
            $table->unsignedBigInteger('ng_count')->default(0);
            $table->unsignedBigInteger('duration_total_sec')->default(0);
            $table->unsignedBigInteger('duration_samples')->default(0);
            $table->unsignedInteger('min_duration_sec')->nullable();
            $table->unsignedInteger('max_duration_sec')->nullable();
            $table->timestamp('aggregated_at')->nullable();

            $table->primary(['date_key', 'internal_id'], 'pk_pdia_date_internal');
            $table->index(['month_key', 'internal_id'], 'idx_pdia_month_internal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_daily_inspector_aggregates');
        Schema::dropIfExists('report_monthly_aggregates');
        Schema::dropIfExists('report_daily_aggregates');
    }
};
