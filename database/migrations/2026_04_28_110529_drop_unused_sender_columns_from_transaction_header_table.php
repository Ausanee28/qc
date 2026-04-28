<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropColumn(['sender_department', 'sender_messenger']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->string('sender_department')->nullable();
            $table->string('sender_messenger')->nullable();
        });
    }
};
