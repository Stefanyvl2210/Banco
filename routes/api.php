<?php

use App\Http\Controllers\CuentaController;
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

//Users routes
Route::get('/user', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);
Route::put('/user/{id}', [UserController::class, 'update']);
Route::delete('/user/{id}', [UserController::class, 'destroy']);

//Cuenta routes
Route::get('/cuenta', [CuentaController::class, 'index']);
Route::post('/cuenta', [CuentaController::class, 'store']);
Route::put('/cuenta/{id}', [CuentaController::class, 'update']);
Route::delete('/cuenta/{id}', [CuentaController::class, 'destroy']);

//Transacciones routes
Route::get('/transaccion', [CuentaController::class, 'index']);
Route::post('/transaccion', [CuentaController::class, 'store']);
Route::put('/transaccion/{id}', [CuentaController::class, 'update']);
Route::delete('/transaccion/{id}', [CuentaController::class, 'destroy']);
