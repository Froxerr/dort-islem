<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile settings
            $table->string('name')->nullable(); // Ad soyad için
            $table->string('profile_image')->nullable();
            $table->text('bio')->nullable();

            // Account security
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('login_notifications')->default(false);

            // Game preferences
            $table->unsignedBigInteger('default_difficulty_id')->nullable();
            $table->unsignedBigInteger('favorite_topic_id')->nullable();
            $table->boolean('auto_next_question')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('sound_effects')->default(true);
            $table->boolean('animations')->default(true);
            $table->enum('theme', ['light', 'dark', 'auto'])->default('light');

            // Email notifications
            $table->boolean('email_achievements')->default(true);
            $table->boolean('email_level_up')->default(true);
            $table->boolean('email_weekly_summary')->default(false);
            $table->boolean('email_reminders')->default(false);

            // Push notifications
            $table->boolean('push_achievements')->default(true);
            $table->boolean('push_level_up')->default(true);
            $table->boolean('push_quiz_complete')->default(true);

            // Privacy settings
            $table->enum('profile_visibility', ['public', 'friends', 'private'])->default('public');
            $table->boolean('show_stats')->default(true);
            $table->boolean('show_achievements')->default(true);
            $table->boolean('show_activity')->default(true);

            // Foreign keys
            $table->foreign('default_difficulty_id')->references('id')->on('difficulty_levels')->onDelete('set null');
            $table->foreign('favorite_topic_id')->references('id')->on('topics')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first (if they exist)
            try {
                $table->dropForeign(['default_difficulty_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, ignore
            }
            
            try {
                $table->dropForeign(['favorite_topic_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, ignore
            }

            // Check if columns exist before dropping them
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('users', 'profile_image')) {
                $table->dropColumn('profile_image');
            }
            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }
            if (Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->dropColumn('two_factor_enabled');
            }
            if (Schema::hasColumn('users', 'login_notifications')) {
                $table->dropColumn('login_notifications');
            }
            if (Schema::hasColumn('users', 'default_difficulty_id')) {
                $table->dropColumn('default_difficulty_id');
            }
            if (Schema::hasColumn('users', 'favorite_topic_id')) {
                $table->dropColumn('favorite_topic_id');
            }
            if (Schema::hasColumn('users', 'auto_next_question')) {
                $table->dropColumn('auto_next_question');
            }
            if (Schema::hasColumn('users', 'show_correct_answers')) {
                $table->dropColumn('show_correct_answers');
            }
            if (Schema::hasColumn('users', 'sound_effects')) {
                $table->dropColumn('sound_effects');
            }
            if (Schema::hasColumn('users', 'animations')) {
                $table->dropColumn('animations');
            }
            if (Schema::hasColumn('users', 'theme')) {
                $table->dropColumn('theme');
            }
            if (Schema::hasColumn('users', 'email_achievements')) {
                $table->dropColumn('email_achievements');
            }
            if (Schema::hasColumn('users', 'email_level_up')) {
                $table->dropColumn('email_level_up');
            }
            if (Schema::hasColumn('users', 'email_weekly_summary')) {
                $table->dropColumn('email_weekly_summary');
            }
            if (Schema::hasColumn('users', 'email_reminders')) {
                $table->dropColumn('email_reminders');
            }
            if (Schema::hasColumn('users', 'push_achievements')) {
                $table->dropColumn('push_achievements');
            }
            if (Schema::hasColumn('users', 'push_level_up')) {
                $table->dropColumn('push_level_up');
            }
            if (Schema::hasColumn('users', 'push_quiz_complete')) {
                $table->dropColumn('push_quiz_complete');
            }
            if (Schema::hasColumn('users', 'profile_visibility')) {
                $table->dropColumn('profile_visibility');
            }
            if (Schema::hasColumn('users', 'show_stats')) {
                $table->dropColumn('show_stats');
            }
            if (Schema::hasColumn('users', 'show_achievements')) {
                $table->dropColumn('show_achievements');
            }
            if (Schema::hasColumn('users', 'show_activity')) {
                $table->dropColumn('show_activity');
            }
        });
    }
};
