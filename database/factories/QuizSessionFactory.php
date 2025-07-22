<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Topic;
use App\Models\DifficultyLevel;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\quizSession>
 */
class QuizSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Önce rastgele bir zorluk seviyesi seçelim ki XP çarpanını bilelim
        $difficultyLevel = DifficultyLevel::inRandomOrder()->first();

        // Testteki toplam soru ve doğru cevap sayısını rastgele belirleyelim
        $totalQuestions = $this->faker->numberBetween(15, 30);
        $correctAnswers = $this->faker->numberBetween(5, $totalQuestions); // Doğru sayısı toplamı geçemez

        // Basit bir puan ve xp hesaplaması yapalım
        $score = $correctAnswers * 10;
        $xp_earned = (int)($score * $difficultyLevel->xp_multiplier);

        return [
            // Veritabanından rastgele bir kullanıcı, konu ve zorluk seviyesi ID'si al
            'user_id' => User::inRandomOrder()->first()->id,
            'topic_id' => Topic::inRandomOrder()->first()->id,
            'difficulty_level_id' => $difficultyLevel->id,

            // Hesaplanan değerleri ata
            'score' => $score,
            'xp_earned' => $xp_earned,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,

            // Geçmiş bir yıla ait rastgele bir tarih oluştur
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
