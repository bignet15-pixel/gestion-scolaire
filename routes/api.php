<?php

use App\Http\Controllers\Api\Parent\AuthController;
use App\Http\Controllers\Api\Parent\EnfantController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent')->name('api.parent.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::post('/password/forgot', [AuthController::class, 'sendPasswordOtp'])->name('password.forgot');
    Route::post('/password/verify-otp', [AuthController::class, 'verifyPasswordOtp'])->name('password.verify-otp');
    Route::post('/password/reset', [AuthController::class, 'resetPasswordWithOtp'])->name('password.reset');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

        Route::get('/enfants', [EnfantController::class, 'index'])->name('enfants.index');
        Route::get('/enfants/{eleve}/dashboard', [EnfantController::class, 'dashboard'])->name('enfants.dashboard');
    });
});
