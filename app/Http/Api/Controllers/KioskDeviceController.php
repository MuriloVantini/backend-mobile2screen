<?php

namespace App\Http\Api\Controllers;

use App\Http\Resources\AlertDeliveryResource;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Services\OperationalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoints consumidos pelo player de TV (Raspberry Pi real ou simulador).
 *
 * A autenticação é feita pelo connection_token do dispositivo
 */
class KioskDeviceController extends Controller
{
    public function __construct(
        private readonly OperationalNotificationService $notifications,
    ) {}

    public function connect(Request $request, Device $device): JsonResponse
    {
        if (! $this->hasValidToken($request, $device)) {
            return $this->unauthorized();
        }

        $this->markOnline($request, $device);
        $device->loadMissing('user');

        return response()->json([
            'message' => 'Dispositivo conectado',
            'data' => [
                'id' => $device->id,
                'device_id' => $device->id,
                'name' => $device->name,
                'profile_name' => $device->user?->name,
                'profile_image_url' => $device->user?->profile_image_path
                    ? Storage::disk('public')->url($device->user->profile_image_path)
                    : null,
                'connected_at' => now(),
                'websocket' => [
                    'key' => config('broadcasting.connections.reverb.key'),
                    'host' => config('broadcasting.connections.reverb.options.host'),
                    'port' => (int) config('broadcasting.connections.reverb.options.port'),
                    'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                    'channel' => "private-device.{$device->id}",
                ],
            ],
        ]);
    }

    public function authorizeBroadcasting(Request $request, Device $device): JsonResponse
    {
        if (! $this->hasValidToken($request, $device)) {
            return $this->unauthorized();
        }

        $validated = $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string'],
        ]);

        $expectedChannel = "private-device.{$device->id}";
        if (! hash_equals($expectedChannel, $validated['channel_name'])) {
            return response()->json(['message' => 'Canal WebSocket não autorizado'], 403);
        }

        $key = config('broadcasting.connections.reverb.key');
        $secret = config('broadcasting.connections.reverb.secret');
        if (! is_string($key) || $key === '' || ! is_string($secret) || $secret === '') {
            return response()->json(['message' => 'Reverb não configurado'], 503);
        }

        $signature = hash_hmac(
            'sha256',
            "{$validated['socket_id']}:{$validated['channel_name']}",
            $secret,
        );

        return response()->json([
            'auth' => "{$key}:{$signature}",
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

        $device->loadMissing('user');

        return response()->json([
            'message' => 'Heartbeat registrado',
            'data' => [
                'profile_name' => $device->user?->name,
                'profile_image_url' => $device->user?->profile_image_path
                    ? Storage::disk('public')->url($device->user->profile_image_path)
                    : null,
            ],
        ]);
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
        $wasOffline = ! $device->is_online;
        $updates = [
            'is_online' => true,
            'last_seen' => now(),
            'ip_address' => $request->ip() ?: $device->ip_address,
        ];

        if ($metadata !== null) {
            $updates['metadata'] = $metadata;
        }

        $device->update($updates);

        if ($wasOffline) {
            $this->notifications->deviceConnected($device);
        }
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Token do dispositivo inválido'], 401);
    }
}
