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
        Schema::create('quizSession', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('topic_id')->constrained('topics');
            $table->foreignId('difficulty_level_id')->constrained('difficulty_levels');
            $table->integer('score');
            $table->integer('xp_earned');
            $table->smallInteger('total_questions');
            $table->smallInteger('correct_answers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizSession');
    }
};
