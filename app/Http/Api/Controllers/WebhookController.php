<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Jobs\DeliverWebhook;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Throwable;

class WebhookController extends Controller
{
    /**
     * Lista todos os webhooks do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = $request->user()
            ->webhooks()
            ->withCount('logs')
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

        $shouldBeActive = $validated['is_active'] ?? true;
        $validated['user_id'] = $request->user()->id;
        $validated['is_active'] = false;
        $webhook = Webhook::create($validated);

        try {
            $this->deliverTest($webhook);
        } catch (Throwable) {
            $webhook->delete();

            return response()->json([
                'message' => 'A URL do webhook não respondeu com sucesso. O cadastro não foi realizado.',
            ], 422);
        }

        $webhook->update(['is_active' => $shouldBeActive]);

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

        $mustValidateEndpoint = array_key_exists('url', $validated)
            || array_key_exists('secret', $validated);
        $originalEndpoint = $webhook->only(['url', 'secret']);

        $webhook->update($validated);

        if ($mustValidateEndpoint) {
            try {
                $this->deliverTest($webhook);
            } catch (Throwable) {
                $webhook->update($originalEndpoint);

                return response()->json([
                    'message' => 'A nova URL do webhook não respondeu com sucesso. A configuração anterior foi mantida.',
                ], 422);
            }
        }

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

        try {
            $this->deliverTest($webhook);
        } catch (Throwable) {
            return response()->json([
                'message' => 'A URL do webhook não respondeu com sucesso.',
            ], 422);
        }

        return response()->json([
            'message' => 'Webhook de teste enviado com sucesso',
        ]);
    }

    private function deliverTest(Webhook $webhook): void
    {
        DeliverWebhook::dispatchSync($webhook->id, [
            'id' => (string) Str::uuid(),
            'event' => 'webhook.test',
            'occurred_at' => now()->toIso8601String(),
            'data' => [
                'message' => 'Teste de conectividade do Mobile2Screen',
                'webhook_id' => $webhook->id,
            ],
        ], true);
    }
}
