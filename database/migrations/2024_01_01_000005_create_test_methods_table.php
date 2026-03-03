<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('Test_Methods', function (Blueprint $table) {
            $table->increments('method_id');
            $table->string('method_name', 100);
            $table->string('tool_name', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Test_Methods');
    }
};
