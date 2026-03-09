<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Requests\StoreAlertRequest;
use App\Http\Api\Requests\UpdateAlertDeliveryStatusRequest;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
    /**
     * Lista todos os alertas do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->alerts()->with('tags');

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

        return response()->json([
            'success' => true,
            'data' => $alerts
        ]);
    }

    /**
     * Cria e envia um novo alerta
     */
    public function store(StoreAlertRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
                    'success' => false,
                    'message' => 'Nenhum dispositivo encontrado com as tags fornecidas'
                ], 422);
            }

            // Criar registros de entrega para cada dispositivo
            foreach ($devices as $device) {
                AlertDelivery::create([
                    'alert_id' => $alert->id,
                    'device_id' => $device->id,
                    'status' => $device->is_online ? 'pending' : 'failed',
                    'error_message' => !$device->is_online ? 'Dispositivo offline' : null,
                ]);
            }

            DB::commit();

            $alert->load(['tags', 'deliveries.device']);

            // Aqui você pode disparar um evento/job para enviar via WebSocket
            // event(new AlertSent($alert));

            return response()->json([
                'success' => true,
                'message' => 'Alerta enviado com sucesso',
                'data' => [
                    'alert' => $alert,
                    'devices_count' => $devices->count(),
                    'online_devices' => $devices->where('is_online', true)->count(),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar alerta: ' . $e->getMessage()
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
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $alert->load(['tags', 'deliveries.device']);

        // Estatísticas de entrega
        $stats = [
            'total_deliveries' => $alert->deliveries->count(),
            'delivered' => $alert->deliveries->where('status', 'delivered')->count(),
            'pending' => $alert->deliveries->where('status', 'pending')->count(),
            'failed' => $alert->deliveries->where('status', 'failed')->count(),
            'acknowledged' => $alert->deliveries->where('status', 'acknowledged')->count(),
            'dismissed' => $alert->deliveries->where('status', 'dismissed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'alert' => $alert,
                'stats' => $stats
            ]
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
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $deliveries = $alert->deliveries()->with('device')->get();

        return response()->json([
            'success' => true,
            'data' => $deliveries
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

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado com sucesso'
        ]);
    }

    /**
     * Reenvia alerta para dispositivos que falharam
     */
    public function retry(Request $request, Alert $alert): JsonResponse
    {
        // Verifica se o alerta pertence ao usuário
        if ($alert->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $failedDeliveries = $alert->deliveries()
            ->where('status', 'failed')
            ->with('device')
            ->get();

        $retried = 0;
        foreach ($failedDeliveries as $delivery) {
            if ($delivery->device->is_online) {
                $delivery->update([
                    'status' => 'pending',
                    'retry_count' => $delivery->retry_count + 1,
                    'error_message' => null
                ]);
                $retried++;
                
                // Disparar evento para reenvio
                // event(new AlertRetry($delivery));
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reenvio iniciado para {$retried} dispositivos"
        ]);
    }
}
