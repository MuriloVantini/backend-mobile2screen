<?php

namespace App\Http\Api\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * Lista todas as API Keys do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $apiKeys = $request->user()
            ->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get()
            ->makeHidden('key_hash');

        return response()->json([
            'success' => true,
            'data' => $apiKeys
        ]);
    }

    /**
     * Cria uma nova API Key
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'expires_at' => 'nullable|date|after:now'
        ]);

        // Gerar chave única
        $key = 'sk_' . Str::random(48);
        $validated['key_hash'] = hash('sha256', $key);
        $validated['user_id'] = $request->user()->id;

        $apiKey = ApiKey::create($validated);

        // Retornar a chave apenas nesta resposta (nunca mais será exibida)
        return response()->json([
            'success' => true,
            'message' => 'API Key criada com sucesso. Guarde-a em local seguro, ela não será exibida novamente.',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $key, // Mostrar apenas uma vez
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at
            ]
        ], 201);
    }

    /**
     * Atualiza uma API Key (apenas nome e status)
     */
    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        // Verifica se a API Key pertence ao usuário
        if ($apiKey->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean'
        ]);

        $apiKey->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'API Key atualizada com sucesso',
            'data' => $apiKey->makeHidden('key_hash')
        ]);
    }

    /**
     * Remove uma API Key
     */
    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        // Verifica se a API Key pertence ao usuário
        if ($apiKey->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API Key removida com sucesso'
        ]);
    }
}
