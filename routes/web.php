<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\QuizSessionController;
use App\Http\Controllers\ProfileController;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/main', [MainController::class, 'main'])->name('main');

// Quiz session route
Route::post('/quiz-sessions', [QuizSessionController::class, 'store'])->name('quiz-sessions.store');
Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
