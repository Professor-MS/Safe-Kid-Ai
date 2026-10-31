<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All protected FamilyTrack routes require a verified Firebase Bearer token.
|
*/

Route::middleware('firebase.auth')->group(function () {
    // Register local profile with role and name after Firebase Auth signup
    Route::post('/auth/register-profile', [AuthController::class, 'registerProfile']);

    // Retrieve current authenticated user profile
    Route::get('/me', [UserController::class, 'me']);

    // Upsert FCM device push token
    Route::put('/me/fcm-token', [UserController::class, 'updateFcmToken']);
});

