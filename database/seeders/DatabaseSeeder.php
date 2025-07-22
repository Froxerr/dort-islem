<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BadgeSeeder::class,
            DifficultyLevelSeeder::class,
            LevelsSeeder::class,
            TopicSeeder::class,
            UserSeeder::class,
            UserBadgeSeeder::class,
            QuizSessionSeeder::class,
        ]);
    }
}



