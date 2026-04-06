<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('Internal_Users', 'is_active')) {
            Schema::table('Internal_Users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('role');
                $table->index('is_active', 'internal_users_is_active_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('Internal_Users', 'is_active')) {
            Schema::table('Internal_Users', function (Blueprint $table) {
                $table->dropIndex('internal_users_is_active_idx');
                $table->dropColumn('is_active');
            });
        }
    }
};
