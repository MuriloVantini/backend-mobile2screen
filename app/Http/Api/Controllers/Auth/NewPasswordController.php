<?php

namespace App\Http\Api\Controllers\Auth;

use App\Http\Api\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use App\Http\Requests\Auth\ValidateResetPinRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Validate reset PIN without changing the password.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validatePin(ValidateResetPinRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->ensureValidPin($validated['email'], $validated['pin_code']);

        Cache::put(
            $this->pinValidatedCacheKey($validated['email']),
            true,
            now()->addMinutes((int) config('auth.passwords.users.expire', 60))
        );

        return response()->json([
            'status' => 'PIN valido.',
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(NewPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! Cache::get($this->pinValidatedCacheKey($validated['email']))) {
            throw ValidationException::withMessages([
                'pin_code' => ['Valide o PIN antes de redefinir a senha.'],
            ]);
        }

        $user = $this->ensureValidPin($validated['email'], $validated['pin_code']);

        $user->forceFill([
            'password' => Hash::make($request->string('password')),
        ])->save();

        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $validated['email'])
            ->delete();

        Cache::forget($this->pinValidatedCacheKey($validated['email']));

        event(new PasswordReset($user));

        return response()->json(['status' => 'Senha redefinida com sucesso.']);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    private function ensureValidPin(string $email, string $pin): User
    {
        $user = User::query()->where('email', $email)->first();
        $resetToken = DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $email)
            ->first();

        $isExpired = ! $resetToken
            || ! $resetToken->created_at
            || now()->diffInMinutes($resetToken->created_at) > config('auth.passwords.users.expire', 60);

        if (! $user || $isExpired || ! Hash::check($pin, $resetToken->token)) {
            throw ValidationException::withMessages([
                'pin_code' => ['PIN invalido ou expirado.'],
            ]);
        }

        return $user;
    }

    private function pinValidatedCacheKey(string $email): string
    {
        return 'password-reset-pin-validated:' . mb_strtolower($email);
    }
}
