<?php

namespace App\Services;

use App\Exceptions\PlanLimitExceededException;
use App\Models\User;

class PlanLimitService
{
    public function ensureCanCreateDevice(User $user): void
    {
        $limit = $user->plan?->max_devices;
        if (! is_int($limit) || $limit < 0) {
            return;
        }

        $current = $user->devices()->count();
        if ($current >= $limit) {
            throw new PlanLimitExceededException('devices', $limit, $current);
        }
    }

    public function ensureCanCreateAlert(User $user): void
    {
        $limit = $user->plan?->max_alerts_per_month;
        if (! is_int($limit) || $limit < 0) {
            return;
        }

        $current = $user->alerts()
            ->whereBetween('sent_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($current >= $limit) {
            throw new PlanLimitExceededException('alerts', $limit, $current);
        }
    }
}
