<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PlanLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $resource,
        public readonly int $limit,
        public readonly int $current,
    ) {
        parent::__construct("Limite do plano atingido para {$resource}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'plan_limit_reached',
            'resource' => $this->resource,
            'limit' => $this->limit,
            'current' => $this->current,
        ], 429);
    }
}
