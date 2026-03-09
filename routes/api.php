<?php

use App\Http\Api\Controllers\Auth\AuthController;
use App\Http\Api\Controllers\AlertController;
use App\Http\Api\Controllers\ApiKeyController;
use App\Http\Api\Controllers\DeviceController;
use App\Http\Api\Controllers\PlanController;
use App\Http\Api\Controllers\StatisticsController;
use App\Http\Api\Controllers\TagController;
use App\Http\Api\Controllers\UserController;
use App\Http\Api\Controllers\UserSettingController;
use App\Http\Api\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// ROTAS PÚBLICAS
// ============================================

// Autenticação
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'store']);

// Planos (visualização pública)
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{plan}', [PlanController::class, 'show']);

// ============================================
// ROTAS PROTEGIDAS (requer autenticação)
// ============================================

Route::middleware(['auth:sanctum'])->group(function () {
    
    // ========== USUÁRIO ==========
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('plan');
        return response()->json(['success' => true, 'data' => $user]);
    });
    Route::apiResource('users', UserController::class);
    Route::post('/logout', [AuthController::class, 'destroy']);

    // ========== CONFIGURAÇÕES DO USUÁRIO ==========
    Route::get('/settings', [UserSettingController::class, 'show']);
    Route::put('/settings', [UserSettingController::class, 'update']);

    // ========== DISPOSITIVOS ==========
    Route::get('/devices/latest', [DeviceController::class, 'latest']);
    Route::apiResource('devices', DeviceController::class);
    Route::post('/devices/{device}/heartbeat', [DeviceController::class, 'heartbeat']);
    Route::post('/devices/{device}/regenerate-token', [DeviceController::class, 'regenerateToken']);

    // ========== TAGS ==========
    Route::apiResource('tags', TagController::class);
    Route::get('/tags/{tag}/devices', [TagController::class, 'devices']);

    // ========== ALERTAS ==========
    Route::apiResource('alerts', AlertController::class)->only(['index', 'store', 'show']);
    Route::get('/alerts/{alert}/deliveries', [AlertController::class, 'deliveries']);
    Route::post('/alerts/{alert}/retry', [AlertController::class, 'retry']);
    Route::patch('/deliveries/{delivery}/status', [AlertController::class, 'updateDeliveryStatus']);

    // ========== API KEYS ==========
    Route::apiResource('api-keys', ApiKeyController::class)->except(['show']);

    // ========== WEBHOOKS ==========
    Route::apiResource('webhooks', WebhookController::class);
    Route::get('/webhooks/{webhook}/logs', [WebhookController::class, 'logs']);
    Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test']);

    // ========== ESTATÍSTICAS ==========
    Route::prefix('statistics')->group(function () {
        Route::get('/dashboard', [StatisticsController::class, 'dashboard']);
        Route::get('/daily', [StatisticsController::class, 'daily']);
        Route::get('/alerts-by-type', [StatisticsController::class, 'alertsByType']);
        Route::get('/top-devices', [StatisticsController::class, 'topDevices']);
    });
});
