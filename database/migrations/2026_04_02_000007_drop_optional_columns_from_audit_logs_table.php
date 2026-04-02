<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('Audit_Logs')) {
            return;
        }

        $columnsToDrop = array_values(array_filter([
            'route_name',
            'request_path',
            'ip_address',
            'user_agent',
            'meta_data',
        ], fn (string $column) => Schema::hasColumn('Audit_Logs', $column)));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('Audit_Logs', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('Audit_Logs')) {
            return;
        }

        Schema::table('Audit_Logs', function (Blueprint $table) {
            if (!Schema::hasColumn('Audit_Logs', 'route_name')) {
                $table->string('route_name', 120)->nullable()->after('performed_by_name');
            }

            if (!Schema::hasColumn('Audit_Logs', 'request_path')) {
                $table->string('request_path', 255)->nullable()->after('route_name');
            }

            if (!Schema::hasColumn('Audit_Logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('request_path');
            }

            if (!Schema::hasColumn('Audit_Logs', 'user_agent')) {
                $table->string('user_agent', 512)->nullable()->after('ip_address');
            }

            if (!Schema::hasColumn('Audit_Logs', 'meta_data')) {
                $table->longText('meta_data')->nullable()->after('after_data');
            }
        });
    }
};

