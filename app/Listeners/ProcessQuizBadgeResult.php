<?php

namespace App\Listeners;

use App\Events\QuizCompleted;
use App\Models\Badge;
use App\Models\BadgeTrigger;
use App\Models\QuizSession;
use App\Models\Achievement;
use App\Notifications\BadgeEarned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessQuizBadgeResult
{
    use InteractsWithQueue;

    public function handle(QuizCompleted $event): void
    {
        try {
            $user = $event->user;

            // 1. Kullanıcının zaten sahip olduğu rozetleri al
            $existingBadgeIds = $user->badges()->pluck('badges.id')->toArray();

            // 2. Potansiyel tetikleyicileri getir
            $potentialTriggers = BadgeTrigger::query()
                ->whereNotIn('badge_id', $existingBadgeIds)
                ->with(['badge', 'badge.achievement'])
                ->get();

            if ($potentialTriggers->isEmpty()) {
                Log::info('Potansiyel rozet bulunamadı', [
                    'user_id' => $user->id,
                    'quiz_score' => $event->totalscore
                ]);
                return;
            }

            // 3. Her tetikleyici için koşulları kontrol et
            foreach ($potentialTriggers as $trigger) {
                $isEarned = false;
                $progress = [];

                if ($trigger->trigger_type === 'required_score') {
                    // Toplam skor kontrolü - tüm quizlerin toplamı
                    $totalScore = QuizSession::where('user_id', $user->id)
                        ->when($trigger->topic_id, function($query) use ($trigger) {
                            return $query->where('topic_id', $trigger->topic_id);
                        })
                        ->sum('score');

                    $isEarned = $totalScore >= $trigger->required_score;
                    $progress = [
                        'current' => $totalScore,
                        'required' => $trigger->required_score,
                        'type' => 'score'
                    ];
                }
                elseif ($trigger->trigger_type === 'required_count') {
                    // Quiz sayısı kontrolü
                    $quizCount = QuizSession::where('user_id', $user->id)
                        ->when($trigger->topic_id, function($query) use ($trigger) {
                            return $query->where('topic_id', $trigger->topic_id);
                        })
                        ->count();

                    $isEarned = $quizCount >= $trigger->required_count;
                    $progress = [
                        'current' => $quizCount,
                        'required' => $trigger->required_count,
                        'type' => 'count'
                    ];
                }
                elseif ($trigger->trigger_type === 'streak') {
                    // Üst üste başarı kontrolü
                    $sessions = QuizSession::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->get();

                    $currentStreak = 0;
                    $maxStreak = 0;

                    foreach ($sessions as $session) {
                        $isSuccess = ($session->correct_answers / $session->total_questions) >= 0.8;
                        $isCorrectTopic = !$trigger->topic_id || $session->topic_id == $trigger->topic_id;

                        if ($isSuccess && $isCorrectTopic) {
                            $currentStreak++;
                            $maxStreak = max($maxStreak, $currentStreak);
                        } else {
                            // Eğer başarısız bir test veya farklı konudan bir test varsa seriyi sıfırla
                            if ($trigger->topic_id && $session->topic_id != $trigger->topic_id) {
                                // Farklı konudan test, seriyi etkilemez
                                continue;
                            }
                            $currentStreak = 0;
                        }
                    }

                    $isEarned = $maxStreak >= $trigger->required_count;
                    $progress = [
                        'current' => $maxStreak,
                        'required' => $trigger->required_count,
                        'type' => 'streak'
                    ];

                    Log::info('Streak kontrolü:', [
                        'user_id' => $user->id,
                        'badge_name' => $trigger->badge->name,
                        'topic_id' => $trigger->topic_id,
                        'current_streak' => $maxStreak,
                        'required_streak' => $trigger->required_count,
                        'is_earned' => $isEarned
                    ]);
                }

                if ($isEarned) {
                    try {
                        DB::transaction(function() use ($user, $trigger, $progress) {
                            // Rozeti kullanıcıya ver
                            $user->badges()->attach($trigger->badge_id);
                            
                            // XP ödülünü al
                            $xpReward = $trigger->badge->achievement->xp_reward ?? 0;
                            
                            // Bildirim gönder
                            $user->notify(new BadgeEarned($trigger->badge, $xpReward, $progress));
                            
                            Log::info('Yeni rozet kazanıldı', [
                                'user_id' => $user->id,
                                'badge_id' => $trigger->badge_id,
                                'badge_name' => $trigger->badge->name,
                                'trigger_type' => $trigger->trigger_type,
                                'xp_reward' => $xpReward,
                                'progress' => $progress
                            ]);

                            // XP ödülünü kullanıcıya ekle
                            if ($xpReward > 0) {
                                $user->xp += $xpReward;
                                $user->save();
                            }
                        });
                    } catch (\Exception $e) {
                        Log::error('Rozet verme işlemi sırasında hata:', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id,
                            'badge_id' => $trigger->badge_id
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Badge işlemi sırasında hata:', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id,
                'quiz_score' => $event->totalscore
            ]);
        }
    }
}
