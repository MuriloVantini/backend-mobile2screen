<?php

use App\Http\Api\Controllers\Auth\AuthController;
use App\Http\Api\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rotas Públicas (Login e Registro)
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/login', [AuthController::class, 'store']);

// Rotas Protegidas (Precisa do Token)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rota de Logout (Revogar Token)
    Route::post('/logout', [AuthController::class, 'destroy']);
});
