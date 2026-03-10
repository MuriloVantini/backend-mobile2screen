<?php

namespace App\Http\Api\Controllers;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PlanController extends Controller
{
    /**
     * Lista todos os planos disponíveis
     */
    public function index(): JsonResponse
    {
        $plans = Plan::all();
        
        return response()->json([
            'data' => $plans->map(fn (Plan $plan) => $plan->toResource()),
        ]);
    }

    /**
     * Exibe um plano específico
     */
    public function show(Plan $plan): JsonResponse
    {
        return response()->json([
            'data' => $plan->toResource(),
        ]);
    }
}
