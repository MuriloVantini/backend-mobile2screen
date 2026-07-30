<?php

namespace App\Http\Api\Controllers;

use App\Http\Resources\AlertDeliveryResource;
use App\Models\AlertDelivery;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Endpoints consumidos pelo player de TV (Raspberry Pi real ou simulador).
 *
 * A autenticação é feita pelo connection_token do dispositivo
 */
class KioskDeviceController extends Controller
{
    public function connect(Request $request, Device $device): JsonResponse
    {
        if (! $this->hasValidToken($request, $device)) {
            return $this->unauthorized();
        }

        $this->markOnline($request, $device);

        return response()->json([
            'message' => 'Dispositivo conectado',
            'data' => [
                'device_id' => $device->id,
                'name' => $device->name,
                'connected_at' => now(),
            ],
        ]);
    }

    public function heartbeat(Request $request, Device $device): JsonResponse
    {
        if (! $this->hasValidToken($request, $device)) {
            return $this->unauthorized();
        }

        $validated = $request->validate([
            'metadata' => ['nullable', 'array'],
        ]);

        $this->markOnline($request, $device, $validated['metadata'] ?? null);

        return response()->json(['message' => 'Heartbeat registrado']);
    }

    public function pendingDeliveries(Request $request, Device $device): JsonResponse
    {
        if (! $this->hasValidToken($request, $device)) {
            return $this->unauthorized();
        }

        $this->markOnline($request, $device);

        $deliveries = AlertDelivery::query()
            ->where('device_id', $device->id)
            ->where('status', 'pending')
            ->whereHas('alert', function ($query) use ($device) {
                $query
                    ->where('user_id', $device->user_id)
                    ->where(function ($expirationQuery) {
                        $expirationQuery
                            ->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->with(['alert.tags'])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => AlertDeliveryResource::collection($deliveries),
        ]);
    }

    public function updateDeliveryStatus(Request $request, Device $device, AlertDelivery $delivery): JsonResponse
    {
        if (! $this->hasValidToken($request, $device)) {
            return $this->unauthorized();
        }

        if ($delivery->device_id !== $device->id) {
            return response()->json(['message' => 'Entrega não pertence ao dispositivo'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:delivered,acknowledged,dismissed'],
        ]);

        $now = now();
        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'delivered') {
            $updates['delivered_at'] = $now;
        }

        if ($validated['status'] === 'acknowledged') {
            $updates['acknowledged_at'] = $now;
        }

        if ($validated['status'] === 'dismissed') {
            $updates['dismissed_at'] = $now;
        }

        $delivery->update($updates);
        $this->markOnline($request, $device);

        return response()->json(['message' => 'Status da entrega atualizado']);
    }

    private function hasValidToken(Request $request, Device $device): bool
    {
        $token = $request->header('X-Device-Token');

        return is_string($token)
            && $token !== ''
            && $device->deleted_at === null
            && hash_equals($device->connection_token, $token);
    }

    private function markOnline(Request $request, Device $device, ?array $metadata = null): void
    {
        $updates = [
            'is_online' => true,
            'last_seen' => now(),
            'ip_address' => $request->ip() ?: $device->ip_address,
        ];

        if ($metadata !== null) {
            $updates['metadata'] = $metadata;
        }

        $device->update($updates);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Token do dispositivo inválido'], 401);
    }
}
