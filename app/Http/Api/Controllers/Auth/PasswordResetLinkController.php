<?php

namespace App\Http\Api\Controllers\Auth;

use App\Http\Api\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use App\Models\User;
use App\Notifications\Auth\PasswordResetPinNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset PIN request.
     */
    public function store(PasswordResetLinkRequest $request): JsonResponse
    {
        $validated = $request->validated();
        Cache::forget('password-reset-pin-validated:' . mb_strtolower($validated['email']));

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'PIN de redefinição enviado para o e-mail.',
            ]);
        }

        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->updateOrInsert(
                ['email' => $validated['email']],
                [
                    'token' => Hash::make($pin),
                    'created_at' => now(),
                ]
            );

        $user->notify(new PasswordResetPinNotification($pin));

        return response()->json([
            'status' => 'PIN de redefinição enviado para o e-mail.',
        ]);
    }
}
