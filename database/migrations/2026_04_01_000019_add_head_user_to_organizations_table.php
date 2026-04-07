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
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_user_id')
                ->nullable()
                ->after('name')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('support_units', function (Blueprint $table) {
            $table->foreignId('head_user_id')
                ->nullable()
                ->after('name')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('program_studies', function (Blueprint $table) {
            $table->foreignId('head_user_id')
                ->nullable()
                ->after('name')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_user_id');
        });

        Schema::table('support_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_user_id');
        });

        Schema::table('program_studies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_user_id');
        });
    }
};
