<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSanctumOrApiKey
{
    /**
     * Permite tokens Sanctum da aplicação ou uma API Key ativa da integração.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sanctumUser = Auth::guard('sanctum')->user();

        if ($sanctumUser !== null) {
            $request->setUserResolver(fn () => $sanctumUser);

            return $next($request);
        }

        $plainTextKey = $request->bearerToken();

        if (! is_string($plainTextKey) || ! str_starts_with($plainTextKey, 'sk_')) {
            return $this->unauthenticated();
        }

        $apiKey = ApiKey::query()
            ->with('user')
            ->where('key_hash', hash('sha256', $plainTextKey))
            ->first();

        if (
            $apiKey === null
            || ! $apiKey->is_active
            || ($apiKey->expires_at !== null && $apiKey->expires_at->isPast())
            || $apiKey->user === null
            || $apiKey->user->status !== 'active'
        ) {
            return $this->unauthenticated();
        }

        $user = $apiKey->user;
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        $apiKey->forceFill(['last_used' => now()])->save();

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => 'Não autenticado.'], 401);
    }
}
