<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Departments', function (Blueprint $table) {
            $table->index('department_name', 'idx_departments_name');
        });

        Schema::table('Equipments', function (Blueprint $table) {
            $table->index('equipment_name', 'idx_equipments_name');
        });

        Schema::table('External_Users', function (Blueprint $table) {
            $table->index('external_name', 'idx_external_users_name');
            $table->index(['department_id', 'external_name'], 'idx_external_users_dept_name');
        });

        Schema::table('Test_Methods', function (Blueprint $table) {
            $table->index('method_name', 'idx_test_methods_name');
        });

        Schema::table('Internal_Users', function (Blueprint $table) {
            $table->index('name', 'idx_internal_users_name');
        });

        if (Schema::hasColumn('Internal_Users', 'is_active')) {
            Schema::table('Internal_Users', function (Blueprint $table) {
                $table->index(['is_active', 'name'], 'idx_internal_users_active_name');
            });
        }

        if (Schema::hasColumn('Transaction_Detail', 'deleted_at')) {
            Schema::table('Transaction_Detail', function (Blueprint $table) {
                $table->index(['deleted_at', 'start_time', 'detail_id'], 'idx_td_deleted_start_detail');
                $table->index(['deleted_at', 'judgement', 'start_time', 'detail_id'], 'idx_td_deleted_judge_start_detail');
            });
        } else {
            Schema::table('Transaction_Detail', function (Blueprint $table) {
                $table->index(['start_time', 'detail_id'], 'idx_td_start_detail');
                $table->index(['judgement', 'start_time', 'detail_id'], 'idx_td_judge_start_detail');
            });
        }
    }

    public function down(): void
    {
        Schema::table('Departments', function (Blueprint $table) {
            $table->dropIndex('idx_departments_name');
        });

        Schema::table('Equipments', function (Blueprint $table) {
            $table->dropIndex('idx_equipments_name');
        });

        Schema::table('External_Users', function (Blueprint $table) {
            $table->dropIndex('idx_external_users_name');
            $table->dropIndex('idx_external_users_dept_name');
        });

        Schema::table('Test_Methods', function (Blueprint $table) {
            $table->dropIndex('idx_test_methods_name');
        });

        Schema::table('Internal_Users', function (Blueprint $table) {
            $table->dropIndex('idx_internal_users_name');
        });

        if (Schema::hasColumn('Internal_Users', 'is_active')) {
            Schema::table('Internal_Users', function (Blueprint $table) {
                $table->dropIndex('idx_internal_users_active_name');
            });
        }

        if (Schema::hasColumn('Transaction_Detail', 'deleted_at')) {
            Schema::table('Transaction_Detail', function (Blueprint $table) {
                $table->dropIndex('idx_td_deleted_start_detail');
                $table->dropIndex('idx_td_deleted_judge_start_detail');
            });
        } else {
            Schema::table('Transaction_Detail', function (Blueprint $table) {
                $table->dropIndex('idx_td_start_detail');
                $table->dropIndex('idx_td_judge_start_detail');
            });
        }
    }
};

