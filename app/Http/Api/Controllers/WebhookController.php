<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
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
            'data' => $webhooks->map(fn (Webhook $webhook) => $webhook->toResource()),
        ]);
    }

    /**
     * Cria um novo webhook
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = $request->user()->id;
        $webhook = Webhook::create($validated);

        return response()->json([
            'message' => 'Webhook criado com sucesso',
            'data' => $webhook->toResource(),
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
                'message' => 'Não autorizado'
            ], 403);
        }

        $webhook->loadCount('logs');

        return response()->json([
            'data' => $webhook->toResource(),
        ]);
    }

    /**
     * Atualiza um webhook
     */
    public function update(UpdateWebhookRequest $request, Webhook $webhook): JsonResponse
    {
        // Verifica se o webhook pertence ao usuário
        if ($webhook->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Não autorizado'
            ], 403);
        }

        $validated = $request->validated();

        $webhook->update($validated);

        return response()->json([
            'message' => 'Webhook atualizado com sucesso',
            'data' => $webhook->toResource(),
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
                'message' => 'Não autorizado'
            ], 403);
        }

        $webhook->delete();

        return response()->json([
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
                'message' => 'Não autorizado'
            ], 403);
        }

        $logs = $webhook->logs()
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        $logs->setCollection($logs->getCollection()->map(fn ($log) => $log->toResource()));

        return response()->json([
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
                'message' => 'Não autorizado'
            ], 403);
        }

        // Aqui você implementaria a lógica de envio de teste
        // Por enquanto, apenas retorna sucesso
        
        return response()->json([
            'message' => 'Webhook de teste enviado'
        ]);
    }
}
