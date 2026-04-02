<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Audit_Logs', function (Blueprint $table) {
            $table->bigIncrements('audit_id');
            $table->string('module', 50);
            $table->string('action', 20);
            $table->string('record_type', 80);
            $table->unsignedInteger('record_id')->nullable();
            $table->unsignedInteger('performed_by')->nullable();
            $table->string('performed_by_name', 120)->nullable();
            $table->string('route_name', 120)->nullable();
            $table->string('request_path', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->longText('before_data')->nullable();
            $table->longText('after_data')->nullable();
            $table->longText('meta_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'action'], 'idx_audit_module_action');
            $table->index(['record_type', 'record_id'], 'idx_audit_record');
            $table->index('performed_by', 'idx_audit_actor');
            $table->index('created_at', 'idx_audit_created_at');

            $table->foreign('performed_by', 'fk_audit_logs_user')
                ->references('user_id')
                ->on('Internal_Users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('Audit_Logs', function (Blueprint $table) {
            $table->dropForeign('fk_audit_logs_user');
        });

        Schema::dropIfExists('Audit_Logs');
    }
};

