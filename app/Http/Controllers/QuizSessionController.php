<?php

namespace App\Http\Controllers;

use App\Models\QuizSession;
use App\Models\User;
use App\Services\UserAchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\QuizCompleted;

class QuizSessionController extends Controller
{
    protected $achievementService;

    public function __construct(UserAchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    private function calculateScore(int $correctAnswers, int $totalQuestions): int
    {
        // Temel puan hesaplama: doğru cevap * 10
        return $correctAnswers * 10;
    }

    private function calculateXP(int $score, int $difficultyLevelId): int
    {
        if ($this->lastTotalQuestions == 0) {
            return 0;
        }
        // Doğruluk oranını hesapla
        $accuracyRate = (($score / 10) / $this->lastTotalQuestions) * 100;

        // Bonus çarpanını belirle
        $bonusMultiplier = 1;
        if ($accuracyRate > 95) {
            $bonusMultiplier = 1.5;
        } else if ($accuracyRate > 80) {
            $bonusMultiplier = 1.2;
        } else if ($accuracyRate > 65) {
            $bonusMultiplier = 1.1;
        }

        // Zorluk seviyesi çarpanını al
        $difficultyMultiplier = DB::table('difficulty_levels')
            ->where('id', $difficultyLevelId)
            ->value('xp_multiplier') ?? 1;

        // XP hesaplama: skor * bonus çarpanı * zorluk çarpanı
        return (int) floor($score * $bonusMultiplier * $difficultyMultiplier);
    }

    private $lastTotalQuestions = 0;

    public function store(Request $request)
    {
        Log::info('Quiz Session isteği başladı', [
            'request_data' => $request->all(),
            'auth_check' => Auth::check(),
            'session_data' => session()->all()
        ]);

        try {
            if (!Auth::check()) {
                Log::warning('Kullanıcı girişi yapılmamış');
                return response()->json([
                    'success' => false,
                    'message' => 'Oturum açmanız gerekiyor'
                ], 401);
            }

            $user = Auth::user();
            Log::info('Kullanıcı bilgileri', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Validation
            Log::info('Validation başlıyor', ['input' => $request->all()]);
            $validatedData = $request->validate([
                'topic_id' => 'required|exists:topics,id',
                'difficulty_level_id' => 'required|exists:difficulty_levels,id',
                'score' => 'required|integer|min:0',
                'xp_earned' => 'required|integer|min:0',
                'total_questions' => 'required|integer|min:0',
                'correct_answers' => 'required|integer|min:0'
            ]);

            // Total questions'ı sakla
            $this->lastTotalQuestions = $validatedData['total_questions'];

            // Score ve XP doğrulama
            $calculatedScore = $this->calculateScore(
                $validatedData['correct_answers'],
                $validatedData['total_questions']
            );

            $calculatedXP = $this->calculateXP(
                $calculatedScore,
                $validatedData['difficulty_level_id']
            );

            Log::info('Skor ve XP hesaplaması', [
                'submitted_score' => $validatedData['score'],
                'calculated_score' => $calculatedScore,
                'submitted_xp' => $validatedData['xp_earned'],
                'calculated_xp' => $calculatedXP,
                'total_questions' => $this->lastTotalQuestions,
                'correct_answers' => $validatedData['correct_answers']
            ]);

            // Eğer gönderilen değerler hesaplananlardan farklıysa
            if ($validatedData['score'] !== $calculatedScore || $validatedData['xp_earned'] !== $calculatedXP) {
                Log::warning('Skor veya XP manipülasyon denemesi', [
                    'user_id' => $user->id,
                    'submitted_values' => [
                        'score' => $validatedData['score'],
                        'xp' => $validatedData['xp_earned']
                    ],
                    'calculated_values' => [
                        'score' => $calculatedScore,
                        'xp' => $calculatedXP
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz skor veya XP değeri'
                ], 400);
            }

            // Transaction başlat
            DB::beginTransaction();
            Log::info('Transaction başladı');

            try {
                // Quiz Session oluştur
                $quizSession = QuizSession::create([
                    'user_id' => $user->id,
                    'topic_id' => $validatedData['topic_id'],
                    'difficulty_level_id' => $validatedData['difficulty_level_id'],
                    'score' => $calculatedScore,
                    'xp_earned' => $calculatedXP,
                    'total_questions' => $validatedData['total_questions'],
                    'correct_answers' => $validatedData['correct_answers']
                ]);

                Log::info('Quiz Session oluşturuldu', ['quiz_session' => $quizSession->toArray()]);

                // Achievement işlemi
                Log::info('Achievement işlemi başlıyor', [
                    'user_id' => $user->id,
                    'quiz_session_id' => $quizSession->id
                ]);

                $achievementResult = $this->achievementService->processAchievements($user, $quizSession);

                if (!$achievementResult) {
                    throw new \Exception('Achievement işlemi başarısız oldu');
                }

                // Quiz tamamlanma event'ini tetikle
                Log::info('Quiz tamamlanma event\'i tetikleniyor', [
                    'user_id' => $user->id,
                    'score' => $calculatedScore,
                    'correct_answers' => $validatedData['correct_answers']
                ]);

                event(new QuizCompleted(
                    $user,
                    $calculatedScore,
                    $validatedData['correct_answers'],
                    [
                        'topic_id' => $validatedData['topic_id'],
                        'correct' => $validatedData['correct_answers']
                    ]
                ));

                Log::info('Quiz tamamlanma event\'i tetiklendi');

                // Transaction'ı commit et
                DB::commit();
                Log::info('Transaction başarıyla tamamlandı');

                // Bildirimleri al
                $notifications = $user->notifications()
                    ->whereIn('type', [
                        'App\\Notifications\\BadgeEarned',
                        'App\\Notifications\\LevelUpEarned'
                    ])
                    ->where('created_at', '>=', now()->subSeconds(5))
                    ->get()
                    ->map(function($notification) {
                        return [
                            'id' => $notification->id,
                            'type' => $notification->type,
                            'data' => $notification->data
                        ];
                    })
                    ->values()
                    ->toArray();

                Log::info('Bildirimler alındı', [
                    'notification_count' => count($notifications),
                    'notifications' => $notifications
                ]);

                $response = [
                    'success' => true,
                    'message' => 'Quiz session başarıyla kaydedildi',
                    'data' => $quizSession,
                    'notifications' => $notifications
                ];

                Log::info('Response hazırlandı', [
                    'response' => $response
                ]);

                return response()->json($response);

            } catch (\Exception $e) {
                // Hata durumunda rollback yap
                DB::rollBack();
                Log::error('Transaction hatası, rollback yapıldı', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Quiz Session Kayıt Hatası', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'auth_check' => Auth::check(),
                'user_id' => Auth::id(),
                'session_data' => session()->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Quiz session kaydedilemedi: ' . $e->getMessage()
            ], 500);
        }
    }
}
