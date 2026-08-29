<?php

namespace App\Http\Api\Controllers;

use App\Events\AlertAvailable;
use App\Http\Requests\StoreAlertRequest;
use App\Http\Requests\UpdateAlertDeliveryStatusRequest;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Services\OperationalNotificationService;
use App\Services\PlanLimitService;
use App\Services\RealtimeUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertController extends Controller
{
    public function __construct(
        private readonly OperationalNotificationService $notifications,
        private readonly PlanLimitService $limits,
        private readonly RealtimeUpdateService $realtime,
    ) {}

    /**
     * Lista todos os alertas do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->alerts()
            ->with('tags')
            ->withCount([
                'deliveries',
                'deliveries as received_devices_count' => fn ($query) => $query
                    ->where(function ($receivedQuery) {
                        $receivedQuery
                            ->whereNotNull('delivered_at')
                            ->orWhereIn('status', ['delivered', 'acknowledged', 'dismissed']);
                    }),
                'deliveries as failed_devices_count' => fn ($query) => $query->where('status', 'failed'),
                'deliveries as pending_devices_count' => fn ($query) => $query->where('status', 'pending'),
            ]);

        // Filtros opcionais
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from_date')) {
            $query->where('sent_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('sent_at', '<=', $request->to_date);
        }

        $alerts = $query->orderBy('sent_at', 'desc')->paginate(20);
        $alerts->setCollection($alerts->getCollection()->map(fn (Alert $alert) => $alert->toResource()));

        return response()->json([
            'data' => $alerts,
        ]);
    }

    /**
     * Cria e envia um novo alerta
     */
    public function store(StoreAlertRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $request->user()->loadMissing('plan');
        $this->limits->ensureCanCreateAlert($request->user());

        DB::beginTransaction();

        try {
            // Criar o alerta
            $validated['user_id'] = $request->user()->id;
            $validated['sent_at'] = now();
            $validated['priority'] = $validated['priority'] ?? 0;

            $alert = Alert::create($validated);

            // Associar tags
            $alert->tags()->attach($validated['tags']);

            // Buscar dispositivos associados às tags
            $deviceIds = DB::table('device_tags')
                ->whereIn('tag_id', $validated['tags'])
                ->pluck('device_id')
                ->unique();

            $devices = Device::whereIn('id', $deviceIds)
                ->where('user_id', $request->user()->id)
                ->whereNull('deleted_at')
                ->get();

            if ($devices->isEmpty()) {
                DB::rollback();

                return response()->json([
                    'message' => 'Nenhum dispositivo encontrado com as tags fornecidas',
                ], 422);
            }

            // Criar registros de entrega para cada dispositivo
            $pendingDeliveries = collect();
            foreach ($devices as $device) {
                $delivery = AlertDelivery::create([
                    'alert_id' => $alert->id,
                    'device_id' => $device->id,
                    'status' => 'pending',
                    'error_message' => null,
                ]);

                if ($delivery->status === 'pending') {
                    $pendingDeliveries->push($delivery);
                }
            }

            DB::commit();

            $alert->load(['tags', 'deliveries.device']);

            foreach ($pendingDeliveries as $delivery) {
                $this->broadcastDelivery($delivery);
            }

            $this->notifications->alertLimitReached($request->user());
            $this->realtime->publish($request->user()->id, 'alerts');

            return response()->json([
                'message' => 'Alerta enviado com sucesso',
                'data' => [
                    'alert' => $alert->toResource(),
                    'devices_count' => $devices->count(),
                    'online_devices' => $devices->where('is_online', true)->count(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Erro ao enviar alerta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe um alerta específico
     */
    public function show(Request $request, Alert $alert): JsonResponse
    {
        // Verifica se o alerta pertence ao usuário
        if ($alert->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $alert->load(['tags', 'deliveries.device']);

        // Estatísticas de entrega
        $stats = [
            'total_deliveries' => $alert->deliveries->count(),
            'delivered' => $alert->deliveries
                ->filter(fn (AlertDelivery $delivery) => $delivery->delivered_at !== null
                    || in_array($delivery->status, ['delivered', 'acknowledged', 'dismissed'], true))
                ->count(),
            'pending' => $alert->deliveries->where('status', 'pending')->count(),
            'failed' => $alert->deliveries->where('status', 'failed')->count(),
            'acknowledged' => $alert->deliveries->where('status', 'acknowledged')->count(),
            'dismissed' => $alert->deliveries->where('status', 'dismissed')->count(),
        ];

        return response()->json([
            'data' => [
                'alert' => $alert->toResource(),
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Lista entregas de um alerta
     */
    public function deliveries(Request $request, Alert $alert): JsonResponse
    {
        // Verifica se o alerta pertence ao usuário
        if ($alert->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $deliveries = $alert->deliveries()->with('device')->get();

        return response()->json([
            'data' => $deliveries->map(fn (AlertDelivery $delivery) => $delivery->toResource()),
        ]);
    }

    /**
     * Atualiza status de entrega (usado pelos dispositivos)
     */
    public function updateDeliveryStatus(UpdateAlertDeliveryStatusRequest $request, AlertDelivery $delivery): JsonResponse
    {
        $validated = $request->validated();
        $now = now();
        $updateData = ['status' => $validated['status']];

        switch ($validated['status']) {
            case 'delivered':
                $updateData['delivered_at'] = $now;
                break;
            case 'acknowledged':
                $updateData['acknowledged_at'] = $now;
                break;
            case 'dismissed':
                $updateData['dismissed_at'] = $now;
                break;
        }

        $delivery->update($updateData);
        if ($validated['status'] === 'failed') {
            $delivery->loadMissing('alert.user');
            $this->notifications->alertFailed($delivery->alert->user, $delivery->alert, 1);
        }
        $this->realtime->publish($delivery->alert()->value('user_id'), 'deliveries');

        return response()->json([
            'message' => 'Status atualizado com sucesso',
        ]);
    }

    /**
     * Reenvia o alerta para todos os dispositivos originalmente associados.
     */
    public function retry(Request $request, Alert $alert): JsonResponse
    {
        // Verifica se o alerta pertence ao usuário
        if ($alert->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $deliveries = $alert->deliveries()
            ->with('device')
            ->get();

        $retried = 0;
        $offline = 0;

        foreach ($deliveries as $delivery) {
            if (! $delivery->device?->is_online) {
                $delivery->update([
                    'status' => 'pending',
                    'error_message' => null,
                ]);
                $offline++;

                continue;
            }

            $delivery->update([
                'status' => 'pending',
                'acknowledged_at' => null,
                'dismissed_at' => null,
                'retry_count' => $delivery->retry_count + 1,
                'error_message' => null,
            ]);

            $retried++;
            $this->broadcastDelivery($delivery);
        }

        $this->realtime->publish($request->user()->id, 'deliveries');

        return response()->json([
            'message' => "Reenvio iniciado para {$retried} dispositivos",
            'data' => [
                'retried_devices' => $retried,
                'offline_devices' => $offline,
            ],
        ]);
    }

    private function broadcastDelivery(AlertDelivery $delivery): void
    {
        try {
            AlertAvailable::dispatch($delivery);
        } catch (\Throwable $exception) {
            Log::warning('Não foi possível notificar o kiosk pelo Reverb.', [
                'delivery_id' => $delivery->id,
                'device_id' => $delivery->device_id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
