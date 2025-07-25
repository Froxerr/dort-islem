<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'requirement_type',
        'requirement_value',
        'xp_reward',
        'has_badge',
        'topic_id'
    ];

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Kullanıcının bu başarım için ilerleme durumunu kontrol et
     */
    public function checkProgress(User $user): array
    {
        $isCompleted = false;
        $progress = 0;

        // Önce topic_id kontrolü yapalım
        $baseQuery = QuizSession::where('user_id', $user->id);
        if ($this->topic_id) {
            $baseQuery->where('topic_id', $this->topic_id);
            
            // Konu bazlı başarım için hiç test yoksa direkt 0 dön
            $testCount = $baseQuery->count();
            if ($testCount === 0) {
                Log::info("Başarım kontrolü: Konu için hiç test yok", [
                    'achievement' => $this->name,
                    'topic_id' => $this->topic_id,
                    'user_id' => $user->id
                ]);
                return [
                    'is_completed' => false,
                    'progress' => 0,
                    'current_value' => 0,
                    'required_value' => $this->requirement_value
                ];
            }
        }

        // Toplam test sayısını al
        $testCount = $baseQuery->count();

        switch ($this->requirement_type) {
            case 'unique_badges':
                // Kullanıcının kazandığı benzersiz rozet sayısını kontrol et
                $uniqueBadgeCount = $user->badges()
                    ->distinct()
                    ->count();

                $progress = min(100, ($uniqueBadgeCount / $this->requirement_value) * 100);
                $isCompleted = $uniqueBadgeCount >= $this->requirement_value;

                Log::info("Başarım kontrolü: Benzersiz rozetler", [
                    'achievement' => $this->name,
                    'unique_badges' => $uniqueBadgeCount,
                    'required' => $this->requirement_value,
                    'progress' => $progress
                ]);

                return [
                    'is_completed' => $isCompleted,
                    'progress' => round($progress),
                    'current_value' => $uniqueBadgeCount,
                    'required_value' => $this->requirement_value
                ];

            case 'quiz_count':
                // Quiz sayısı kontrolü
                $progress = min(100, ($testCount / $this->requirement_value) * 100);
                $isCompleted = $testCount >= $this->requirement_value;

                Log::info("Başarım kontrolü: Quiz sayısı", [
                    'achievement' => $this->name,
                    'test_count' => $testCount,
                    'required' => $this->requirement_value,
                    'progress' => $progress,
                    'topic_id' => $this->topic_id
                ]);

                return [
                    'is_completed' => $isCompleted,
                    'progress' => round($progress),
                    'current_value' => $testCount,
                    'required_value' => $this->requirement_value
                ];

            case 'success_rate':
                // Başarı oranı kontrolü
                if ($testCount === 0) {
                    Log::info("Başarım kontrolü: Başarı oranı - Test yok", [
                        'achievement' => $this->name,
                        'topic_id' => $this->topic_id
                    ]);
                    return [
                        'is_completed' => false,
                        'progress' => 0,
                        'current_value' => 0,
                        'required_value' => 3
                    ];
                }

                $successfulQuizzes = $baseQuery
                    ->where(DB::raw('(correct_answers * 100 / total_questions)'), '>=', $this->requirement_value)
                    ->count();

                // En az 3 test gerekiyor ve hepsinde başarı oranı yeterli olmalı
                $requiredQuizCount = 3;
                $progress = min(100, ($successfulQuizzes / $requiredQuizCount) * 100);
                $isCompleted = $successfulQuizzes >= $requiredQuizCount;

                Log::info("Başarım kontrolü: Başarı oranı", [
                    'achievement' => $this->name,
                    'successful_quizzes' => $successfulQuizzes,
                    'required' => $requiredQuizCount,
                    'progress' => $progress,
                    'topic_id' => $this->topic_id,
                    'total_tests' => $testCount
                ]);

                return [
                    'is_completed' => $isCompleted,
                    'progress' => round($progress),
                    'current_value' => $successfulQuizzes,
                    'required_value' => $requiredQuizCount
                ];

            case 'perfect_score':
                // Tam puan kontrolü
                if ($testCount === 0) {
                    Log::info("Başarım kontrolü: Tam puan - Test yok", [
                        'achievement' => $this->name,
                        'topic_id' => $this->topic_id
                    ]);
                    return [
                        'is_completed' => false,
                        'progress' => 0,
                        'current_value' => 0,
                        'required_value' => $this->requirement_value
                    ];
                }

                $perfectScoreCount = $baseQuery
                    ->whereRaw('correct_answers = total_questions')
                    ->count();

                $progress = min(100, ($perfectScoreCount / $this->requirement_value) * 100);
                $isCompleted = $perfectScoreCount >= $this->requirement_value;

                Log::info("Başarım kontrolü: Tam puan", [
                    'achievement' => $this->name,
                    'perfect_scores' => $perfectScoreCount,
                    'required' => $this->requirement_value,
                    'progress' => $progress,
                    'topic_id' => $this->topic_id,
                    'total_tests' => $testCount
                ]);

                return [
                    'is_completed' => $isCompleted,
                    'progress' => round($progress),
                    'current_value' => $perfectScoreCount,
                    'required_value' => $this->requirement_value
                ];

            case 'streak':
                // Seri kontrolü
                if ($testCount === 0) {
                    Log::info("Başarım kontrolü: Seri - Test yok", [
                        'achievement' => $this->name,
                        'topic_id' => $this->topic_id
                    ]);
                    return [
                        'is_completed' => false,
                        'progress' => 0,
                        'current_value' => 0,
                        'required_value' => $this->requirement_value
                    ];
                }

                $sessions = $baseQuery
                    ->orderBy('created_at', 'desc')
                    ->get();

                $currentStreak = 0;
                $maxStreak = 0;

                foreach ($sessions as $session) {
                    $successRate = ($session->correct_answers * 100) / $session->total_questions;
                    $isSuccess = $successRate >= 80;
                    
                    if ($isSuccess) {
                        $currentStreak++;
                        $maxStreak = max($maxStreak, $currentStreak);
                    } else {
                        $currentStreak = 0;
                    }

                    Log::info("Seri kontrolü - Test", [
                        'achievement' => $this->name,
                        'session_id' => $session->id,
                        'topic_id' => $session->topic_id,
                        'success_rate' => $successRate,
                        'is_success' => $isSuccess,
                        'current_streak' => $currentStreak,
                        'max_streak' => $maxStreak
                    ]);
                }

                $progress = min(100, ($maxStreak / $this->requirement_value) * 100);
                $isCompleted = $maxStreak >= $this->requirement_value;

                Log::info("Başarım kontrolü: Seri", [
                    'achievement' => $this->name,
                    'max_streak' => $maxStreak,
                    'required' => $this->requirement_value,
                    'progress' => $progress,
                    'topic_id' => $this->topic_id,
                    'total_tests' => $testCount
                ]);

                return [
                    'is_completed' => $isCompleted,
                    'progress' => round($progress),
                    'current_value' => $maxStreak,
                    'required_value' => $this->requirement_value
                ];

            default:
                Log::warning("Başarım kontrolü: Bilinmeyen tip", [
                    'achievement' => $this->name,
                    'type' => $this->requirement_type
                ]);
                return [
                    'is_completed' => false,
                    'progress' => 0,
                    'current_value' => 0,
                    'required_value' => $this->requirement_value
                ];
        }
    }
}
