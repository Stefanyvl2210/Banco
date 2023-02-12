<?php

use App\Http\Controllers\CuentaController;
use App\Http\Controllers\TransaccionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthenticateUserController;
use Illuminate\Http\Request;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Auth router
Route::post('/login', [AuthenticateUserController::class, 'store']);


//Users routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);
Route::put('/user/{id}', [UserController::class, 'update']);
Route::delete('/user/{id}', [UserController::class, 'destroy']);

//Cuenta routes
Route::get('/cuentas', [CuentaController::class, 'index']);
Route::post('/cuenta', [CuentaController::class, 'store']);
Route::put('/cuenta/{id}', [CuentaController::class, 'update']);
Route::delete('/cuenta/{id}', [CuentaController::class, 'destroy']);

//Transacciones routes
Route::get('/transacciones', [TransaccionController::class, 'index']);
Route::post('/transaccion', [TransaccionController::class, 'store']);
Route::put('/deposit', [TransaccionController::class, 'deposit']);
Route::put('/withdrawal', [TransaccionController::class, 'withdrawal']);
Route::put('/transaccion/{id}', [TransaccionController::class, 'update']);
Route::delete('/transaccion/{id}', [TransaccionController::class, 'destroy']);
