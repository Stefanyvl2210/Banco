<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthenticateUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public auth routes.
Route::post('/login', [AuthenticateUserController::class, 'store']);
Route::post('/register', [UserController::class, 'store']);
Route::post('/user', [UserController::class, 'store']);

// Protected routes that require authentication.
Route::middleware('auth:sanctum')->group(function () {
    // User profile and session management routes.
    Route::get('/me', [AuthenticateUserController::class, 'me']);
    Route::patch('/me', [UserController::class, 'updateProfile']);
    Route::post('/logout', [AuthenticateUserController::class, 'destroy']);

    // User management routes (admin only).
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::delete('/user/{id}', [UserController::class, 'destroy']);
    });

    // Account management routes.
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/account', [AccountController::class, 'store']);
    Route::get('/account/{account}', [AccountController::class, 'show']);
    Route::delete('/account/{account}', [AccountController::class, 'destroy']);

    // Transaction management routes.
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transaction', [TransactionController::class, 'store']);
    Route::put('/deposit', [TransactionController::class, 'deposit']);
    Route::put('/withdrawal', [TransactionController::class, 'withdrawal']);
});
