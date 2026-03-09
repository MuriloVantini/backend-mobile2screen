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

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [$request->user()->load('plan')],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $actor = $request->user();
        $isPublicRegistration = $actor === null;

        if (! $isPublicRegistration && $actor->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Nao autorizado',
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
            'success' => true,
            'message' => $isPublicRegistration
                ? 'Usuario registrado com sucesso'
                : 'Usuario criado com sucesso',
            'data' => $user,
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autorizado',
            ], 403);
        }

        $user->load('plan');

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autorizado',
            ], 403);
        }

        $validated = $request->validated();

        if ($request->user()->role !== 'admin') {
            unset($validated['status'], $validated['role'], $validated['plan_id']);
        }

        $user->update($validated);
        $user->load('plan');

        return response()->json([
            'success' => true,
            'message' => 'Usuario atualizado com sucesso',
            'data' => $user,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->role !== 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autorizado',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario removido com sucesso',
        ]);
    }
}
