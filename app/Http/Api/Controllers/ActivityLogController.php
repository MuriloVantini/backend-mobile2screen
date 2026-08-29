<?php

namespace App\Http\Api\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:120'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = ActivityLog::query()
            ->with('user')
            ->when($validated['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($validated['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', "%{$action}%"))
            ->when($validated['from_date'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', $date))
            ->when($validated['to_date'] ?? null, fn ($query, $date) => $query->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->latest('created_at')
            ->paginate($validated['per_page'] ?? 25);

        $logs->setCollection($logs->getCollection()->map(fn (ActivityLog $log) => $log->toResource()));

        return response()->json(['data' => $logs]);
    }
}
