<?php

namespace App\Http\Api\Controllers;

use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    /**
     * Lista todos os webhooks do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = $request->user()
            ->webhooks()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $webhooks
        ]);
    }

    /**
     * Cria um novo webhook
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:alert.sent,alert.delivered,alert.failed,device.online,device.offline,device.added',
            'is_active' => 'sometimes|boolean'
        ]);

        $validated['user_id'] = $request->user()->id;
        $webhook = Webhook::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Webhook criado com sucesso',
            'data' => $webhook
        ], 201);
    }

    /**
     * Exibe um webhook específico
     */
    public function show(Request $request, Webhook $webhook): JsonResponse
    {
        // Verifica se o webhook pertence ao usuário
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $webhook->loadCount('logs');

        return response()->json([
            'success' => true,
            'data' => $webhook
        ]);
    }

    /**
     * Atualiza um webhook
     */
    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        // Verifica se o webhook pertence ao usuário
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'secret' => 'nullable|string|max:255',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string|in:alert.sent,alert.delivered,alert.failed,device.online,device.offline,device.added',
            'is_active' => 'sometimes|boolean'
        ]);

        $webhook->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Webhook atualizado com sucesso',
            'data' => $webhook
        ]);
    }

    /**
     * Remove um webhook
     */
    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        // Verifica se o webhook pertence ao usuário
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook removido com sucesso'
        ]);
    }

    /**
     * Lista logs de um webhook
     */
    public function logs(Request $request, Webhook $webhook): JsonResponse
    {
        // Verifica se o webhook pertence ao usuário
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $logs = $webhook->logs()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Testa um webhook
     */
    public function test(Request $request, Webhook $webhook): JsonResponse
    {
        // Verifica se o webhook pertence ao usuário
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        // Aqui você implementaria a lógica de envio de teste
        // Por enquanto, apenas retorna sucesso
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook de teste enviado'
        ]);
    }
}
