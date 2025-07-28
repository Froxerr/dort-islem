<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Topic;
use App\Models\DifficultyLevel;
use App\Models\QuizSession;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProfileSettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        $user = Auth::user();
        $topics = Topic::all();
        $difficulties = DifficultyLevel::all();

        return view('profile.profile-settings', compact('user', 'topics', 'difficulties'));
    }

    /**
     * Update user settings based on tab
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $tab = $request->input('tab');

        try {
            switch ($tab) {
                case 'profile':
                    return $this->updateProfile($request, $user);
                case 'account':
                    return $this->updateAccount($request, $user);
                case 'preferences':
                    return $this->updatePreferences($request, $user);
                case 'notifications':
                    return $this->updateNotifications($request, $user);
                case 'privacy':
                    return $this->updatePrivacy($request, $user);
                default:
                    return back()->with('error', 'Geçersiz ayar kategorisi.');
            }
        } catch (\Exception $e) {
            Log::error('Settings update error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'tab' => $tab,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Ayarlar güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Update profile information
     */
    private function updateProfile(Request $request, User $user)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:500',
        ];

        // Only validate profile_image if it's actually uploaded
        if ($request->hasFile('profile_image')) {
            $validationRules['profile_image'] = 'image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        }

        $request->validate($validationRules);

        // Update basic info
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->bio = $request->bio;

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Store new image
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $imagePath;
        }

        $user->save();

        return back()->with('success', 'Profil bilgileriniz başarıyla güncellendi!');
    }

    /**
     * Update account security settings
     */
    private function updateAccount(Request $request, User $user)
    {
        // Handle password change
        if ($request->filled('current_password')) {
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'Mevcut şifreniz yanlış.');
            }

            $user->password = Hash::make($request->password);
        }

        // Handle 2FA toggle
        $twoFactorEnabled = $request->has('two_factor_enabled');
        if ($twoFactorEnabled && !$user->two_factor_enabled) {
            // Enable 2FA
            $user->enableTwoFactorAuth();
        } elseif (!$twoFactorEnabled && $user->two_factor_enabled) {
            // Disable 2FA
            $user->disableTwoFactorAuth();
        }

        // Update login notifications
        $user->login_notifications = $request->has('login_notifications');

        $user->save();

        return back()->with('success', 'Hesap güvenlik ayarlarınız başarıyla güncellendi!');
    }

    /**
     * Get 2FA QR Code
     */
    public function getTwoFactorQrCode()
    {
        $user = Auth::user();

        if (!$user->two_factor_secret) {
            $google2fa = app('pragmarx.google2fa');
            $user->forceFill([
                'two_factor_secret' => encrypt($google2fa->generateSecretKey()),
            ])->save();
        }

        $qrCodeUrl = $user->generateTwoFactorQrCode();
        $qrCode = QrCode::size(200)->generate($qrCodeUrl);

        return response()->json([
            'qr_code' => $qrCode,
            'secret' => decrypt($user->two_factor_secret)
        ]);
    }

    /**
     * Verify 2FA setup
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();

        if ($user->verifyTwoFactorCode($request->code)) {
            $user->two_factor_enabled = true;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'İki faktörlü kimlik doğrulama başarıyla etkinleştirildi!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Geçersiz kod. Lütfen tekrar deneyin.'
        ], 422);
    }

    /**
     * Update game preferences
     */
    private function updatePreferences(Request $request, User $user)
    {
        $request->validate([
            'default_difficulty' => 'nullable|exists:difficulty_levels,id',
            'favorite_topic' => 'nullable|exists:topics,id',
            'theme' => 'required|in:light,dark,auto'
        ]);

        // Update game preferences
        $user->default_difficulty_id = $request->default_difficulty ?: null;
        $user->favorite_topic_id = $request->favorite_topic ?: null;
        $user->auto_next_question = $request->has('auto_next_question');
        $user->show_correct_answers = $request->has('show_correct_answers');
        $user->sound_effects = $request->has('sound_effects');
        $user->theme = $request->theme;

        $user->save();

        return back()->with('success', 'Oyun tercihleriniz başarıyla güncellendi!');
    }

    /**
     * Update notification settings
     */
    private function updateNotifications(Request $request, User $user)
    {
        // Update email notifications
        $user->email_achievements = $request->has('email_achievements');
        $user->email_level_up = $request->has('email_level_up');
        $user->email_weekly_summary = $request->has('email_weekly_summary');
        $user->email_reminders = $request->has('email_reminders');

        // Update push notifications
        $user->push_achievements = $request->has('push_achievements');
        $user->push_level_up = $request->has('push_level_up');
        $user->push_quiz_complete = $request->has('push_quiz_complete');

        $user->save();

        return back()->with('success', 'Bildirim ayarlarınız başarıyla güncellendi!');
    }

    /**
     * Update privacy settings
     */
    private function updatePrivacy(Request $request, User $user)
    {
        $request->validate([
            'profile_visibility' => 'required|in:public,friends,private'
        ]);

        // Update privacy settings
        $user->profile_visibility = $request->profile_visibility;
        $user->show_stats = $request->has('show_stats');
        $user->show_achievements = $request->has('show_achievements');
        $user->show_activity = $request->has('show_activity');

        $user->save();

        return back()->with('success', 'Gizlilik ayarlarınız başarıyla güncellendi!');
    }

    /**
     * Clear all user progress
     */
    public function clearProgress()
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Reset user stats
            $user->xp = 0;
            $user->level = 1;
            $user->save();

            // Clear quiz sessions
            QuizSession::where('user_id', $user->id)->delete();

            // Clear user badges
            DB::table('user_badges')->where('user_id', $user->id)->delete();

            // Clear user achievements
            DB::table('user_achievements')->where('user_id', $user->id)->delete();

            DB::commit();

            Log::info('User progress cleared', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'İlerlemeniz başarıyla sıfırlandı.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Clear progress error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'İlerleme sıfırlanırken bir hata oluştu.'
            ], 500);
        }
    }

    /**
     * Delete user account completely
     */
    public function deleteAccount()
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Delete profile image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Delete user data
            QuizSession::where('user_id', $user->id)->delete();
            DB::table('user_badges')->where('user_id', $user->id)->delete();
            DB::table('user_achievements')->where('user_id', $user->id)->delete();

            // Log before deletion
            Log::info('User account deleted', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email
            ]);

            // Logout and delete user
            Auth::logout();
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hesabınız başarıyla silindi.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Delete account error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hesap silinirken bir hata oluştu.'
            ], 500);
        }
    }

    /**
     * Get user settings as JSON (for API)
     */
    public function getSettings()
    {
        $user = Auth::user();

        return response()->json([
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'bio' => $user->bio,
                'profile_image' => $user->profile_image ? Storage::url($user->profile_image) : null,
            ],
            'preferences' => [
                'default_difficulty_id' => $user->default_difficulty_id,
                'favorite_topic_id' => $user->favorite_topic_id,
                'auto_next_question' => $user->auto_next_question,
                'show_correct_answers' => $user->show_correct_answers,
                'sound_effects' => $user->sound_effects,
                'animations' => $user->animations,
                'theme' => $user->theme,
            ],
            'notifications' => [
                'email_achievements' => $user->email_achievements,
                'email_level_up' => $user->email_level_up,
                'email_weekly_summary' => $user->email_weekly_summary,
                'email_reminders' => $user->email_reminders,
                'push_achievements' => $user->push_achievements,
                'push_level_up' => $user->push_level_up,
                'push_quiz_complete' => $user->push_quiz_complete,
            ],
            'privacy' => [
                'profile_visibility' => $user->profile_visibility,
                'show_stats' => $user->show_stats,
                'show_achievements' => $user->show_achievements,
                'show_activity' => $user->show_activity,
            ]
        ]);
    }

    /**
     * Export user data (GDPR compliance)
     */
    public function exportData()
    {
        $user = Auth::user();

        try {
            $userData = [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'bio' => $user->bio,
                    'level' => $user->level,
                    'xp' => $user->xp,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'quiz_sessions' => QuizSession::where('user_id', $user->id)
                    ->with(['topic', 'difficultyLevel'])
                    ->get()
                    ->toArray(),
                'badges' => DB::table('user_badges')
                    ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
                    ->where('user_badges.user_id', $user->id)
                    ->select('badges.name', 'badges.description', 'user_badges.earned_at')
                    ->get()
                    ->toArray(),
                'achievements' => DB::table('user_achievements')
                    ->join('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
                    ->where('user_achievements.user_id', $user->id)
                    ->select('achievements.name', 'achievements.description', 'user_achievements.completed_at')
                    ->get()
                    ->toArray(),
                'settings' => [
                    'theme' => $user->theme,
                    'sound_effects' => $user->sound_effects,
                    'animations' => $user->animations,
                    'profile_visibility' => $user->profile_visibility,
                    // Add other non-sensitive settings
                ]
            ];

            $fileName = 'user_data_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';

            return response()->json($userData)
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            Log::error('Export data error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Veri dışa aktarılırken bir hata oluştu.'
            ], 500);
        }
    }
}
