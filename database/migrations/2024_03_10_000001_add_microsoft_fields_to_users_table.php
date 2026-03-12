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
            $table->string('microsoft_id')->unique()->nullable()->after('id');
            $table->string('avatar')->nullable()->after('email');
            $table->string('department')->nullable()->after('avatar');
            $table->string('job_title')->nullable()->after('department');
            $table->boolean('is_active')->default(true)->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'microsoft_id',
                'avatar',
                'department',
                'job_title',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
