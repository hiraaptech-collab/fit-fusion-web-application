<?php
use App\Http\Controllers\ClientReviewController;
use App\Http\Controllers\Gym\ReviewManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/feedback', [ClientReviewController::class, 'publicIndex'])->name('public.feedback');
Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::get('/reviews', [ClientReviewController::class, 'index'])->name('client.reviews');
    Route::post('/reviews', [ClientReviewController::class, 'store'])
        ->middleware('throttle:feedback-submit')->name('client.reviews.store');
});
Route::middleware(['auth', 'role:owner,manager', 'can:feedback.manage'])->prefix('manage')->group(function () {
    Route::get('/feedback', [ReviewManagementController::class, 'index'])->name('gym.reviews');
    Route::put('/feedback/{review}', [ReviewManagementController::class, 'update'])->name('gym.reviews.update');
});
