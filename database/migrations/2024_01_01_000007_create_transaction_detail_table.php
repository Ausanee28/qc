<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('Transaction_Detail', function (Blueprint $table) {
            $table->increments('detail_id');
            $table->unsignedInteger('transaction_id');
            $table->unsignedInteger('method_id');
            $table->unsignedInteger('internal_id');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->useCurrent();
            $table->string('judgement')->nullable();
            $table->string('remark', 255)->nullable();

            $table->foreign('transaction_id')->references('transaction_id')->on('Transaction_Header');
            $table->foreign('method_id')->references('method_id')->on('Test_Methods');
            $table->foreign('internal_id')->references('user_id')->on('Internal_Users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Transaction_Detail');
    }
};
