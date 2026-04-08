<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('Departments', 'is_active')) {
            Schema::table('Departments', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('internal_phone');
                $table->index(['is_active', 'department_name'], 'idx_departments_active_name');
            });
        }

        if (!Schema::hasColumn('Equipments', 'is_active')) {
            Schema::table('Equipments', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('equipment_name');
                $table->index(['is_active', 'equipment_name'], 'idx_equipments_active_name');
            });
        }

        if (!Schema::hasColumn('Test_Methods', 'is_active')) {
            Schema::table('Test_Methods', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('equipment_id');
                $table->index(['is_active', 'method_name'], 'idx_test_methods_active_name');
            });
        }

        if (!Schema::hasColumn('External_Users', 'is_active')) {
            Schema::table('External_Users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('department_id');
                $table->index(['is_active', 'external_name'], 'idx_external_users_active_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('External_Users', 'is_active')) {
            Schema::table('External_Users', function (Blueprint $table) {
                $table->dropIndex('idx_external_users_active_name');
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('Test_Methods', 'is_active')) {
            Schema::table('Test_Methods', function (Blueprint $table) {
                $table->dropIndex('idx_test_methods_active_name');
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('Equipments', 'is_active')) {
            Schema::table('Equipments', function (Blueprint $table) {
                $table->dropIndex('idx_equipments_active_name');
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('Departments', 'is_active')) {
            Schema::table('Departments', function (Blueprint $table) {
                $table->dropIndex('idx_departments_active_name');
                $table->dropColumn('is_active');
            });
        }
    }
};

