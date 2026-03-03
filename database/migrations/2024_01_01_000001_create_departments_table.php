<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('Departments', function (Blueprint $table) {
            $table->increments('department_id');
            $table->string('department_name', 100);
            $table->string('internal_phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Departments');
    }
};
