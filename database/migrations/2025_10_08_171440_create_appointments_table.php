<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('patient_name');
            $table->string('patient_email');
            $table->dateTime('date');
            $table->string('type'); // e.g., "checkup", "urgent"
            $table->string('status')->default('pending'); // pending/done/cancelled
            $table->foreignId('created_by')->constrained('users'); // nurse
            $table->foreignId('updated_by')->nullable()->constrained('users'); // doctor
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
