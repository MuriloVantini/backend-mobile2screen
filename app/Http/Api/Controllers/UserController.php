<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role === 'admin') {
            $users = User::with('plan')->orderByDesc('created_at')->paginate(20);
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

        return response()->json([
            'message' => 'Usuario atualizado com sucesso',
            'data' => $user->toResource(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuário removido com sucesso',
        ]);
    }
}
