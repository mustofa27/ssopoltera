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
        Schema::create('direction_board', function (Blueprint $table) {
            $table->id();
            $table->foreignId('director_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vice_director_1_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vice_director_2_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vice_director_3_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direction_board');
    }
};
