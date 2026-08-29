<?php

namespace App\Http\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RealtimeController extends Controller
{
    public function config(): JsonResponse
    {
        return response()->json(['data' => [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme'),
        ]]);
    }

    public function authorizeChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string'],
        ]);

        $allowedChannels = ["private-user.{$request->user()->id}"];
        if ($request->user()->role === 'admin') {
            $allowedChannels[] = 'private-admin-dashboard';
        }

        if (! in_array($validated['channel_name'], $allowedChannels, true)) {
            return response()->json(['message' => 'Canal WebSocket não autorizado'], 403);
        }

        $key = config('broadcasting.connections.reverb.key');
        $secret = config('broadcasting.connections.reverb.secret');
        if (! is_string($key) || $key === '' || ! is_string($secret) || $secret === '') {
            return response()->json(['message' => 'Reverb não configurado'], 503);
        }

        $signature = hash_hmac('sha256', "{$validated['socket_id']}:{$validated['channel_name']}", $secret);

        return response()->json(['auth' => "{$key}:{$signature}"]);
    }
}
