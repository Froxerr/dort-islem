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
        Schema::create('badge_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('trigger_type',50);
            $table->integer('required_count')->nullable();
            $table->integer('required_score')->nullable();
            $table->integer('time_limit')->nullable();
            $table->foreignId('badge_id')->nullable()->constrained('badges')->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_triggers');
    }
};
