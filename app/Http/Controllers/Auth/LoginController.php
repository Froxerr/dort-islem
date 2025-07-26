<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\LoginNotification;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (auth()->check()) {
            return redirect()->route('main');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            Log::info('🔐 KULLANICI GİRİŞ YAPTI', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // Send login notification if enabled
            if ($user->login_notifications) {
                Log::info('📧 Giriş bildirimi gönderiliyor...');
                $this->sendLoginNotification($user, $request);
            } else {
                Log::info('📧 Giriş bildirimi devre dışı', [
                    'user_id' => $user->id,
                    'login_notifications' => $user->login_notifications
                ]);
            }
            
            return redirect()->intended('main');
        }

        return back()->withErrors([
            'email' => 'Girdiğiniz bilgiler hatalı.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('index');
    }

    /**
     * Send login notification
     */
    private function sendLoginNotification($user, $request)
    {
        Log::info('=== LOGIN NOTIFICATION BAŞLADI ===', [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'login_notifications_enabled' => $user->login_notifications
        ]);
        
        try {
            $loginData = [
                'ip' => $request->ip(),
                'device' => $this->getUserAgent($request),
                'location' => $this->getLocationFromIp($request->ip()),
                'date' => now()->format('d.m.Y'),
                'time' => now()->format('H:i:s'),
            ];

            Log::info('Login data prepared', $loginData);

            $user->notify(new LoginNotification($loginData));
            
            Log::info('✅ LOGIN NOTIFICATION BAŞARIYLA GÖNDERİLDİ', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $loginData['ip'],
                'device' => $loginData['device']
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ LOGIN NOTIFICATION HATASI', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get user agent info
     */
    private function getUserAgent($request)
    {
        $userAgent = $request->userAgent();
        
        // Simple browser detection
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome Browser';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Firefox Browser';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari Browser';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'Edge Browser';
        } else {
            return 'Bilinmeyen Cihaz';
        }
    }

    /**
     * Get location from IP (simplified)
     */
    private function getLocationFromIp($ip)
    {
        // For localhost/development
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Yerel Bilgisayar';
        }
        
        // You can integrate with a real IP geolocation service here
        // For now, return a generic location
        return 'Bilinmeyen Konum';
    }
} 