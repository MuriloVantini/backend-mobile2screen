<?php

namespace App\Http\Api\Controllers;

use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TagController extends Controller
{
    /**
     * Lista todas as tags do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $tags = $request->user()
            ->tags()
            ->withCount('devices', 'alerts')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Cria uma nova tag
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:50'
        ]);

        // Verifica se já existe uma tag com esse nome para o usuário
        $existingTag = $request->user()
            ->tags()
            ->where('name', $validated['name'])
            ->first();

        if ($existingTag) {
            return response()->json([
                'success' => false,
                'message' => 'Já existe uma tag com esse nome'
            ], 422);
        }

        $validated['user_id'] = $request->user()->id;
        $tag = Tag::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag criada com sucesso',
            'data' => $tag
        ], 201);
    }

    /**
     * Exibe uma tag específica
     */
    public function show(Request $request, Tag $tag): JsonResponse
    {
        // Verifica se a tag pertence ao usuário
        if ($tag->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $tag->loadCount('devices', 'alerts');

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    /**
     * Atualiza uma tag
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        // Verifica se a tag pertence ao usuário
        if ($tag->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'nullable|string|max:50'
        ]);

        // Verifica duplicação de nome se estiver sendo alterado
        if (isset($validated['name']) && $validated['name'] !== $tag->name) {
            $existingTag = $request->user()
                ->tags()
                ->where('name', $validated['name'])
                ->where('id', '!=', $tag->id)
                ->first();

            if ($existingTag) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma tag com esse nome'
                ], 422);
            }
        }

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag atualizada com sucesso',
            'data' => $tag
        ]);
    }

    /**
     * Remove uma tag
     */
    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        // Verifica se a tag pertence ao usuário
        if ($tag->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag removida com sucesso'
        ]);
    }

    /**
     * Lista dispositivos de uma tag
     */
    public function devices(Request $request, Tag $tag): JsonResponse
    {
        // Verifica se a tag pertence ao usuário
        if ($tag->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado'
            ], 403);
        }

        $devices = $tag->devices()->get();

        return response()->json([
            'success' => true,
            'data' => $devices
        ]);
    }
}
