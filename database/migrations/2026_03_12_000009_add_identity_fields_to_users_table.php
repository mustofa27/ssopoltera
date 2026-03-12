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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type')->nullable()->after('job_title');
            $table->string('employee_type')->nullable()->after('user_type');
            $table->string('nip')->nullable()->unique()->after('employee_type');
            $table->string('nrp')->nullable()->unique()->after('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropUnique(['nrp']);
            $table->dropColumn(['user_type', 'employee_type', 'nip', 'nrp']);
        });
    }
};
