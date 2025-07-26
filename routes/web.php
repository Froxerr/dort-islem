<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\QuizSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MessageController;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/main', [MainController::class, 'main'])->name('main');

// Quiz session route
Route::post('/quiz-sessions', [QuizSessionController::class, 'store'])->name('quiz-sessions.store');

// Leaderboard
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

// Profil Hub ve ilgili rotalar
Route::middleware(['auth'])->group(function () {
    Route::get('/profile-hub', [ProfileController::class, 'hub'])->name('profile.hub');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.details');
    Route::get('/profile/achievements', [ProfileController::class, 'achievements'])->name('profile.achievements');
    Route::get('/profile/history', [ProfileController::class, 'history'])->name('profile.history');
    // Settings routes - using dedicated controller
    Route::get('/profile/settings', [ProfileSettingsController::class, 'index'])->name('profile.settings');
    Route::put('/profile/settings', [ProfileSettingsController::class, 'update'])->name('profile.settings.update');
    Route::post('/profile/settings/clear-progress', [ProfileSettingsController::class, 'clearProgress'])->name('profile.settings.clear-progress');
    Route::delete('/profile/settings/delete-account', [ProfileSettingsController::class, 'deleteAccount'])->name('profile.settings.delete-account');
    Route::get('/profile/settings/export', [ProfileSettingsController::class, 'exportData'])->name('profile.settings.export');
    Route::get('/profile/settings/api', [ProfileSettingsController::class, 'getSettings'])->name('profile.settings.api');
    // 2FA routes
    Route::get('/profile/settings/2fa/qr-code', [ProfileSettingsController::class, 'getTwoFactorQrCode'])->name('profile.settings.2fa.qr');
    Route::post('/profile/settings/2fa/verify', [ProfileSettingsController::class, 'verifyTwoFactor'])->name('profile.settings.2fa.verify');
    
    // Arkadaşlık Sistemi
    Route::get('/profile/friends', [FriendshipController::class, 'index'])->name('friends.index');
    Route::get('/profile/friends/search', [FriendshipController::class, 'search'])->name('friends.search');
    Route::get('/profile/friends/view/{id}', [FriendshipController::class, 'viewProfile'])->name('friends.view');
    
    // Arkadaşlık İstekleri
    Route::post('/profile/friends/send-request', [FriendshipController::class, 'sendRequest'])->name('friends.send-request');
    Route::post('/profile/friends/accept-request', [FriendshipController::class, 'acceptRequest'])->name('friends.accept-request');
    Route::post('/profile/friends/reject-request', [FriendshipController::class, 'rejectRequest'])->name('friends.reject-request');
    Route::post('/profile/friends/remove-friend', [FriendshipController::class, 'removeFriend'])->name('friends.remove-friend');
    
    // Mesajlaşma Sistemi
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/friends', [MessageController::class, 'getFriends'])->name('messages.friends');
    Route::post('/messages/conversation', [MessageController::class, 'getOrCreateConversation'])->name('messages.conversation');
    Route::get('/messages/conversation/{conversationId}', [MessageController::class, 'getMessages'])->name('messages.get');
    Route::post('/messages/send', [MessageController::class, 'sendMessage'])->name('messages.send');
    Route::post('/messages/mark-read', [MessageController::class, 'markAsRead'])->name('messages.mark-read');
    Route::get('/messages/unread/{conversationId}', [MessageController::class, 'getUnreadCount'])->name('messages.unread');
    Route::get('/messages/unread-total', [MessageController::class, 'getTotalUnreadCount'])->name('messages.unread-total');
    Route::post('/messages/typing', [MessageController::class, 'typing'])->name('messages.typing');

});

