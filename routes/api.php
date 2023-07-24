<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('password/email', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('email/verify/{user}', [AuthController::class, 'verify'])->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resend'])->name('verification.resend');

//    Route::post('oauth/{driver}', [OAuthController::class, 'redirect']);
//    Route::get('oauth/{driver}/callback', [OAuthController::class, 'handleCallback'])->name('oauth.callback');
//    Route::post('google-one-tab', [OAuthController::class, 'googleOneTab'])->name('oauth.google.one.tab');
});

Route::group(['middleware' => 'auth:sanctum', 'verified'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'me']);
});
