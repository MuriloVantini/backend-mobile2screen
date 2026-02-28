<?php

namespace App\Http\Api\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserSettingController extends Controller
{
    /**
     * Retorna as configurações do usuário
     */
    public function show(Request $request): JsonResponse
    {
        $settings = $request->user()->settings;

        // Se não existir, criar com valores padrão
        if (!$settings) {
            $settings = UserSetting::create([
                'user_id' => $request->user()->id
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Atualiza as configurações do usuário
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notify_alert_failed' => 'sometimes|boolean',
            'notify_device_offline' => 'sometimes|boolean',
            'notify_weekly_report' => 'sometimes|boolean',
            'notify_device_connected' => 'sometimes|boolean',
            'notify_limit_reached' => 'sometimes|boolean',
            'notification_email' => 'nullable|email',
            'notification_phone' => 'nullable|string|max:50',
            'timezone' => 'sometimes|string|max:50',
            'language' => 'sometimes|string|max:10',
            'theme' => 'sometimes|in:light,dark,auto',
        ]);

        $settings = $request->user()->settings;

        if (!$settings) {
            $validated['user_id'] = $request->user()->id;
            $settings = UserSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configurações atualizadas com sucesso',
            'data' => $settings
        ]);
    }
}
