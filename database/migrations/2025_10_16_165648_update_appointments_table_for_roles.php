<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Rename columns if needed
            if (Schema::hasColumn('appointments', 'date')) {
                $table->renameColumn('date', 'appointment_date');
            }

            // Add nurse_id and doctor_id if they don't exist
            if (!Schema::hasColumn('appointments', 'nurse_id')) {
                $table->foreignId('nurse_id')->constrained('users')->after('status');
            }

            if (!Schema::hasColumn('appointments', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable()->constrained('users')->after('nurse_id');
            }

            // Add report column if not exists
            if (!Schema::hasColumn('appointments', 'report')) {
                $table->text('report')->nullable()->after('doctor_id');
            }
        });
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'report')) {
                $table->dropColumn('report');
            }
            if (Schema::hasColumn('appointments', 'doctor_id')) {
                $table->dropForeign(['doctor_id']);
                $table->dropColumn('doctor_id');
            }
            if (Schema::hasColumn('appointments', 'nurse_id')) {
                $table->dropForeign(['nurse_id']);
                $table->dropColumn('nurse_id');
            }

            // Optional: rename back appointment_date
            if (Schema::hasColumn('appointments', 'appointment_date')) {
                $table->renameColumn('appointment_date', 'date');
            }
        });
    }
};
