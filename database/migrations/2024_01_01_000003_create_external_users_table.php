<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('External_Users', function (Blueprint $table) {
            $table->increments('external_id');
            $table->string('external_name', 100);
            $table->unsignedInteger('department_id')->nullable();

            $table->foreign('department_id')->references('department_id')->on('Departments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('External_Users');
    }
};
