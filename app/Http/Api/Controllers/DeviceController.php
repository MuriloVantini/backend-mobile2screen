<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Requests\DeviceHeartbeatRequest;
use App\Http\Api\Requests\StoreDeviceRequest;
use App\Http\Api\Requests\UpdateDeviceRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    /**
     * Lista todos os dispositivos do usuário autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()
            ->devices()
            ->with('tags')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $devices
        ]);
    }

    /**
     * Lista os 5 dispositivos mais recentes do usuário autenticado
     */
    public function latest(Request $request): JsonResponse
    {
        $devices = $request->user()
            ->devices()
            ->with('tags')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    /**
     * Cria um novo dispositivo
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Gerar token único de conexão
        $validated['connection_token'] = Str::random(64);
        $validated['user_id'] = $request->user()->id;

        $device = Device::create($validated);

        // Associar tags se fornecidas
        if (isset($validated['tags'])) {
            $device->tags()->attach($validated['tags']);
        }

        $device->load('tags');

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo criado com sucesso',
            'data' => $device
        ], 201);
    }

    /**
     * Exibe um dispositivo específico
     */
    public function show(Request $request, Device $device): JsonResponse
    {
        // Verifica se o dispositivo pertence ao usuário
        if ($device->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $device->load('tags');

        return response()->json([
            'success' => true,
            'data' => $device
        ]);
    }

    /**
     * Atualiza um dispositivo
     */
    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        // Verifica se o dispositivo pertence ao usuário
        if ($device->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $validated = $request->validated();

        $device->update($validated);

        // Atualizar tags se fornecidas
        if (isset($validated['tags'])) {
            $device->tags()->sync($validated['tags']);
        }

        $device->load('tags');

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo atualizado com sucesso',
            'data' => $device
        ]);
    }

    /**
     * Remove (soft delete) um dispositivo
     */
    public function destroy(Request $request, Device $device): JsonResponse
    {
        // Verifica se o dispositivo pertence ao usuário
        if ($device->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo removido com sucesso'
        ]);
    }

    /**
     * Marca dispositivo como online (heartbeat)
     */
    public function heartbeat(DeviceHeartbeatRequest $request, Device $device): JsonResponse
    {
        $validated = $request->validated();

        $device->update([
            'is_online' => true,
            'last_seen' => now(),
            'ip_address' => $validated['ip_address'] ?? $device->ip_address,
            'metadata' => $validated['metadata'] ?? $device->metadata
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat registrado'
        ]);
    }

    /**
     * Regenera o token de conexão
     */
    public function regenerateToken(Request $request, Device $device): JsonResponse
    {
        // Verifica se o dispositivo pertence ao usuário
        if ($device->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $device->update([
            'connection_token' => Str::random(64)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token regenerado com sucesso',
            'data' => [
                'connection_token' => $device->connection_token
            ]
        ]);
    }
}
