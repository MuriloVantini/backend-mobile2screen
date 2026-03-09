<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\UpdateUserSettingRequest;
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
            'data' => $settings->toResource(),
        ]);
    }

    /**
     * Atualiza as configurações do usuário
     */
    public function update(UpdateUserSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
            'data' => $settings->toResource(),
        ]);
    }
}
