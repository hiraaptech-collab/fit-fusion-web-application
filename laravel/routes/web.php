<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Preserve the original public URLs, including the capitalized BMI filename.
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/index.html', [PublicPageController::class, 'home'])->name('home.index');
Route::get('/about.html', [PublicPageController::class, 'about'])->name('about');
Route::get('/dietplans.html', [PublicPageController::class, 'dietPlans'])->name('dietplans');
Route::get('/workouts.html', [PublicPageController::class, 'workouts'])->name('workouts');
Route::get('/BMI.html', [PublicPageController::class, 'bmi'])->name('bmi');
Route::get('/fusion-ai.html', [PublicPageController::class, 'fusionAI'])->name('fusion-ai');
Route::get('/contact.html', [PublicPageController::class, 'contact'])->name('contact');

// Web middleware keeps CSRF protection enabled for same-origin chat.
Route::post('/api/chat', [ChatController::class, 'store'])
    ->middleware(['throttle:fusion-ai','ai-access'])->name('api.chat');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:forgot-password')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:reset-password')->name('password.update');
});

Route::middleware('auth')->group(function () {
    // A sign-in confirmation, not a management dashboard.
    Route::get('/account', [AuthController::class, 'account'])->name('account');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/client/profile', fn (Request $request) => response()->json([
        'profile' => $request->user()->clientProfile,
    ]))->middleware('role:client')->name('client.profile');

    // Backend foundation only. Manager management screens belong to a later phase.
    Route::post('/owner/managers', [ManagerController::class, 'store'])
        ->middleware(['role:owner', 'can:create-managers', 'throttle:manager-write'])->name('owner.managers.store');
    Route::put('/owner/managers/{manager}/permissions', [ManagerController::class, 'permissions'])
        ->middleware(['role:owner', 'can:create-managers', 'throttle:manager-write'])->name('owner.managers.permissions');
});

require __DIR__.'/gym.php';

require __DIR__.'/reviews.php';

