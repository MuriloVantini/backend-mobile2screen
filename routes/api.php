<?php

use App\Http\Api\Controllers\ActivityLogController;
use App\Http\Api\Controllers\AlertController;
use App\Http\Api\Controllers\ApiKeyController;
use App\Http\Api\Controllers\Auth\AuthController;
use App\Http\Api\Controllers\Auth\NewPasswordController;
use App\Http\Api\Controllers\Auth\PasswordResetLinkController;
use App\Http\Api\Controllers\DeviceController;
use App\Http\Api\Controllers\KioskDeviceController;
use App\Http\Api\Controllers\PlanController;
use App\Http\Api\Controllers\RealtimeController;
use App\Http\Api\Controllers\StatisticsController;
use App\Http\Api\Controllers\TagController;
use App\Http\Api\Controllers\UserController;
use App\Http\Api\Controllers\UserSettingController;
use App\Http\Middleware\AuthenticateSanctumOrApiKey;
use App\Http\Middleware\LogUserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// ROTAS PÚBLICAS
// ============================================

// Autenticação
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'store']);
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/validate-reset-pin', [NewPasswordController::class, 'validatePin']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

// Player de TV/Raspberry Pi. O token do dispositivo é enviado no cabeçalho
Route::prefix('kiosk/devices/{device}')->group(function () {
    Route::post('/connect', [KioskDeviceController::class, 'connect']);
    Route::post('/broadcasting/auth', [KioskDeviceController::class, 'authorizeBroadcasting']);
    Route::post('/heartbeat', [KioskDeviceController::class, 'heartbeat']);
    Route::get('/deliveries', [KioskDeviceController::class, 'pendingDeliveries']);
    Route::patch('/deliveries/{delivery}/status', [KioskDeviceController::class, 'updateDeliveryStatus']);
});

// Planos (visualização pública)
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{plan}', [PlanController::class, 'show']);

// ============================================
// ROTAS PROTEGIDAS (requer autenticação)
// ============================================

// Integrações externas podem criar alertas com uma API Key; tokens Sanctum
// usados pelo painel continuam aceitos no mesmo endpoint.
Route::post('/alerts', [AlertController::class, 'store'])
    ->middleware([AuthenticateSanctumOrApiKey::class, LogUserActivity::class]);

Route::middleware(['auth:sanctum', LogUserActivity::class])->group(function () {

    // ========== USUÁRIO ==========
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('plan');

        return response()->json(['data' => $user->toResource()]);
    });
    Route::post('/users/{user}/profile-image', [UserController::class, 'updateProfileImage']);
    Route::delete('/users/{user}/profile-image', [UserController::class, 'destroyProfileImage']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::apiResource('users', UserController::class);
    Route::post('/logout', [AuthController::class, 'destroy']);

    // ========== TEMPO REAL ==========
    Route::get('/realtime/config', [RealtimeController::class, 'config']);
    Route::post('/realtime/authorize', [RealtimeController::class, 'authorizeChannel']);

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
    Route::apiResource('alerts', AlertController::class)->only(['index', 'show']);
    Route::get('/alerts/{alert}/deliveries', [AlertController::class, 'deliveries']);
    Route::post('/alerts/{alert}/retry', [AlertController::class, 'retry']);
    Route::patch('/deliveries/{delivery}/status', [AlertController::class, 'updateDeliveryStatus']);

    // ========== API KEYS ==========
    Route::apiResource('api-keys', ApiKeyController::class)->except(['show']);

    // ========== AUDITORIA ==========
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // ========== ESTATÍSTICAS ==========
    Route::prefix('statistics')->group(function () {
        Route::get('/admin-dashboard', [StatisticsController::class, 'adminDashboard']);
        Route::get('/dashboard', [StatisticsController::class, 'dashboard']);
        Route::get('/daily', [StatisticsController::class, 'daily']);
        Route::get('/alerts-by-type', [StatisticsController::class, 'alertsByType']);
        Route::get('/top-devices', [StatisticsController::class, 'topDevices']);
    });
});
