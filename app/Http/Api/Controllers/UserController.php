<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileImageRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\RealtimeUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct(private readonly RealtimeUpdateService $realtime) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role === 'admin') {
            $users = User::query()
                ->with(['plan', 'tags'])
                ->withCount(['devices', 'alerts', 'activityLogs', 'deliveries'])
                ->withCount(['deliveries as received_deliveries_count' => fn ($query) => $query
                    ->where(function ($receivedQuery) {
                        $receivedQuery->whereNotNull('delivered_at')
                            ->orWhereIn('status', ['delivered', 'acknowledged', 'dismissed']);
                    })])
                ->orderByDesc('created_at')
                ->paginate(20);
            $users->setCollection($users->getCollection()->map(fn (User $user) => $user->toResource()));

            return response()->json([
                'data' => $users,
            ]);
        }

        return response()->json([
            'data' => [$request->user()->load('plan')->toResource()],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $actor = $request->user();
        $isPublicRegistration = $actor === null;

        if (! $isPublicRegistration && $actor->role !== 'admin') {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $validated = $request->validated();

        if ($isPublicRegistration) {
            // Registro publico nao pode definir privilegios administrativos.
            unset($validated['status'], $validated['role']);
        }

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['role'] = $validated['role'] ?? 'user';

        $user = User::create($validated);
        $user->load('plan');

        return response()->json([
            'message' => $isPublicRegistration
                ? 'Usuário registrado com sucesso'
                : 'Usuário criado com sucesso',
            'data' => $user->toResource(),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $user->load('plan');

        return response()->json([
            'data' => $user->toResource(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $validated = $request->validated();

        if ($request->user()->role !== 'admin') {
            unset($validated['status'], $validated['role'], $validated['plan_id']);
        }

        $user->update($validated);
        $user->load('plan');
        $this->realtime->publish($user->id, 'users');

        return response()->json([
            'message' => 'Usuario atualizado com sucesso',
            'data' => $user->toResource(),
        ]);
    }

    public function updateProfileImage(UpdateProfileImageRequest $request, User $user): JsonResponse
    {
        if (! $this->canManage($request, $user)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $oldPath = $user->profile_image_path;
        $newPath = $request->file('image')->store("profile-images/{$user->id}", 'public');

        $user->forceFill(['profile_image_path' => $newPath])->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Foto de perfil atualizada com sucesso',
            'data' => $user->load('plan')->toResource(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request, User $user): JsonResponse
    {
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $validated = $request->validated();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'A senha atual está incorreta.',
                'errors' => [
                    'current_password' => ['A senha atual está incorreta.'],
                ],
            ], 422);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json([
            'message' => 'Senha alterada com sucesso.',
        ]);
    }

    public function destroyProfileImage(Request $request, User $user): JsonResponse
    {
        if (! $this->canManage($request, $user)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
            $user->forceFill(['profile_image_path' => null])->save();
        }

        return response()->json([
            'message' => 'Foto de perfil removida com sucesso',
            'data' => $user->load('plan')->toResource(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }

        $user->delete();
        $this->realtime->publish($user->id, 'users');

        return response()->json([
            'message' => 'Usuário removido com sucesso',
        ]);
    }

    private function canManage(Request $request, User $user): bool
    {
        return $request->user()->role === 'admin' || $request->user()->id === $user->id;
    }
}
