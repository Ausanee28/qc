<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('Transaction_Header', function (Blueprint $table) {
            $table->increments('transaction_id');
            $table->unsignedInteger('external_id');
            $table->unsignedInteger('internal_id');
            $table->unsignedInteger('equipment_id');
            $table->string('dmc')->nullable();
            $table->string('line')->nullable();
            $table->dateTime('receive_date')->useCurrent();
            $table->dateTime('return_date')->nullable();

            $table->foreign('external_id')->references('external_id')->on('External_Users');
            $table->foreign('internal_id')->references('user_id')->on('Internal_Users');
            $table->foreign('equipment_id')->references('equipment_id')->on('Equipments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Transaction_Header');
    }
};
