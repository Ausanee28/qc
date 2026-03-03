<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('Internal_Users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('user_name');
            $table->string('user_password', 255)->nullable();
            $table->string('employee_id')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('role', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Internal_Users');
    }
};
