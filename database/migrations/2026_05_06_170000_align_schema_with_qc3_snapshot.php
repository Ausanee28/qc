<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Internal_Users')) {
            if (
                DB::connection()->getDriverName() === 'sqlite'
                && Schema::hasColumn('Internal_Users', 'email')
            ) {
                DB::statement('DROP INDEX IF EXISTS internal_users_email_unique');
            }

            $legacyUserColumns = array_values(array_filter([
                'remember_token',
                'email_verified_at',
                'email',
            ], fn (string $column) => Schema::hasColumn('Internal_Users', $column)));

            if ($legacyUserColumns !== []) {
                Schema::table('Internal_Users', function (Blueprint $table) use ($legacyUserColumns) {
                    $table->dropColumn($legacyUserColumns);
                });
            }
        }

        Schema::dropIfExists('password_reset_tokens');

        if (Schema::hasTable('Test_Methods') && Schema::hasColumn('Test_Methods', 'tool_name')) {
            Schema::table('Test_Methods', function (Blueprint $table) {
                $table->dropColumn('tool_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Internal_Users')) {
            Schema::table('Internal_Users', function (Blueprint $table) {
                if (!Schema::hasColumn('Internal_Users', 'email')) {
                    $table->string('email')->nullable()->unique();
                }

                if (!Schema::hasColumn('Internal_Users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable();
                }

                if (!Schema::hasColumn('Internal_Users', 'remember_token')) {
                    $table->rememberToken();
                }
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (Schema::hasTable('Test_Methods') && !Schema::hasColumn('Test_Methods', 'tool_name')) {
            Schema::table('Test_Methods', function (Blueprint $table) {
                $table->string('tool_name', 100)->nullable();
            });
        }
    }
};
