<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\DailyStatisticsRequest;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\StatisticDaily;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Visão administrativa agregada de todo o sistema.
     */
    public function adminDashboard(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $fromDate = now()->subDays(29)->startOfDay();
        $dailyFromDate = now()->subDays(6)->startOfDay();

        $totalUsers = User::query()->count();
        $activeUsers = User::query()->where('status', 'active')->count();
        $suspendedUsers = User::query()->where('status', 'suspended')->count();
        $newUsersToday = User::query()->whereDate('created_at', today())->count();

        $totalDevices = Device::query()->count();
        $onlineDevices = Device::query()->where('is_online', true)->count();
        $offlineDevices = $totalDevices - $onlineDevices;

        $alertsToday = Alert::query()->whereDate('sent_at', today())->count();
        $alertsLast30Days = Alert::query()->where('sent_at', '>=', $fromDate)->count();

        $deliveryQuery = AlertDelivery::query()
            ->whereHas('alert', fn ($query) => $query->where('sent_at', '>=', $fromDate));
        $totalDeliveries = (clone $deliveryQuery)->count();
        $deliveredDeliveries = (clone $deliveryQuery)
            ->where(function ($query) {
                $query->whereNotNull('delivered_at')
                    ->orWhereIn('status', ['delivered', 'acknowledged', 'dismissed']);
            })
            ->count();
        $failedDeliveries = (clone $deliveryQuery)->where('status', 'failed')->count();
        $pendingDeliveries = (clone $deliveryQuery)->where('status', 'pending')->count();
        $deliveryRate = $totalDeliveries > 0
            ? round(($deliveredDeliveries / $totalDeliveries) * 100, 2)
            : 0;

        $daily = collect(range(0, 6))->map(function (int $offset) use ($dailyFromDate) {
            $date = $dailyFromDate->copy()->addDays($offset);
            $alerts = Alert::query()->whereDate('sent_at', $date)->count();
            $dailyDeliveries = AlertDelivery::query()
                ->whereHas('alert', fn ($query) => $query->whereDate('sent_at', $date));
            $total = (clone $dailyDeliveries)->count();
            $delivered = (clone $dailyDeliveries)
                ->where(function ($query) {
                    $query->whereNotNull('delivered_at')
                        ->orWhereIn('status', ['delivered', 'acknowledged', 'dismissed']);
                })
                ->count();
            $failed = (clone $dailyDeliveries)->where('status', 'failed')->count();

            return [
                'date' => $date->toDateString(),
                'alerts_sent' => $alerts,
                'alerts_delivered' => $delivered,
                'alerts_failed' => $failed,
                'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            ];
        });

        $alertsByType = Alert::query()
            ->where('sent_at', '>=', $fromDate)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        $topUsers = User::query()
            ->withCount('devices')
            ->withCount(['alerts' => fn ($query) => $query->where('sent_at', '>=', $fromDate)])
            ->orderByDesc('alerts_count')
            ->limit(5)
            ->get()
            ->map(function (User $user) use ($fromDate) {
                $deliveries = AlertDelivery::query()
                    ->whereHas('alert', fn ($query) => $query
                        ->where('user_id', $user->id)
                        ->where('sent_at', '>=', $fromDate));
                $total = (clone $deliveries)->count();
                $delivered = (clone $deliveries)
                    ->where(function ($query) {
                        $query->whereNotNull('delivered_at')
                            ->orWhereIn('status', ['delivered', 'acknowledged', 'dismissed']);
                    })
                    ->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company' => $user->company,
                    'devices_count' => $user->devices_count,
                    'alerts_count' => $user->alerts_count,
                    'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
                ];
            });

        return response()->json([
            'data' => [
                'generated_at' => now()->toIso8601String(),
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'suspended' => $suspendedUsers,
                    'new_today' => $newUsersToday,
                ],
                'devices' => [
                    'total' => $totalDevices,
                    'online' => $onlineDevices,
                    'offline' => $offlineDevices,
                    'online_percentage' => $totalDevices > 0
                        ? round(($onlineDevices / $totalDevices) * 100, 1)
                        : 0,
                ],
                'alerts' => [
                    'today' => $alertsToday,
                    'last_30_days' => $alertsLast30Days,
                ],
                'deliveries' => [
                    'total' => $totalDeliveries,
                    'delivered' => $deliveredDeliveries,
                    'failed' => $failedDeliveries,
                    'pending' => $pendingDeliveries,
                    'delivery_rate' => $deliveryRate,
                ],
                'daily' => $daily,
                'alerts_by_type' => $alertsByType,
                'top_users' => $topUsers,
            ],
        ]);
    }

    /**
     * Dashboard geral com estatísticas do usuário
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Estatísticas de dispositivos
        $totalDevices = $user->devices()->count();
        $onlineDevices = $user->devices()->where('is_online', true)->count();
        $offlineDevices = $totalDevices - $onlineDevices;

        // Estatísticas de alertas (últimos 30 dias)
        $thirtyDaysAgo = now()->subDays(30);
        $alertsLast30Days = $user->alerts()
            ->where('sent_at', '>=', $thirtyDaysAgo)
            ->count();

        // Estatísticas de entregas
        $deliveryStats = DB::table('alert_deliveries')
            ->join('alerts', 'alerts.id', '=', 'alert_deliveries.alert_id')
            ->where('alerts.user_id', $user->id)
            ->where('alerts.sent_at', '>=', $thirtyDaysAgo)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            )
            ->first();

        // Taxa de entrega
        $deliveryRate = $deliveryStats->total > 0 
            ? round(($deliveryStats->delivered / $deliveryStats->total) * 100, 2) 
            : 0;

        // Alertas hoje
        $alertsToday = $user->alerts()
            ->whereDate('sent_at', today())
            ->count();

        // Tags mais usadas
        $topTags = DB::table('alert_tags')
            ->join('tags', 'tags.id', '=', 'alert_tags.tag_id')
            ->join('alerts', 'alerts.id', '=', 'alert_tags.alert_id')
            ->where('tags.user_id', $user->id)
            ->where('alerts.sent_at', '>=', $thirtyDaysAgo)
            ->select('tags.name', 'tags.color', DB::raw('COUNT(*) as count'))
            ->groupBy('tags.id', 'tags.name', 'tags.color')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'devices' => [
                    'total' => $totalDevices,
                    'online' => $onlineDevices,
                    'offline' => $offlineDevices,
                    'online_percentage' => $totalDevices > 0 ? round(($onlineDevices / $totalDevices) * 100, 1) : 0
                ],
                'alerts' => [
                    'today' => $alertsToday,
                    'last_30_days' => $alertsLast30Days,
                ],
                'deliveries' => [
                    'total' => $deliveryStats->total,
                    'delivered' => $deliveryStats->delivered,
                    'failed' => $deliveryStats->failed,
                    'pending' => $deliveryStats->pending,
                    'delivery_rate' => $deliveryRate
                ],
                'top_tags' => $topTags
            ]
        ]);
    }

    /**
     * Estatísticas diárias
     */
    public function daily(DailyStatisticsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $days = $validated['days'] ?? 30;
        $fromDate = $validated['from_date'] ?? now()->subDays($days)->format('Y-m-d');
        $toDate = $validated['to_date'] ?? now()->format('Y-m-d');

        $statistics = StatisticDaily::where('user_id', $request->user()->id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'data' => $statistics->map(fn (StatisticDaily $statistic) => $statistic->toResource()),
        ]);
    }

    /**
     * Estatísticas por tipo de alerta
     */
    public function alertsByType(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $fromDate = now()->subDays($days);

        $stats = $request->user()
            ->alerts()
            ->where('sent_at', '>=', $fromDate)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Dispositivos mais ativos (que recebem mais alertas)
     */
    public function topDevices(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $fromDate = now()->subDays($days);
        $limit = $request->query('limit', 10);

        $topDevices = DB::table('alert_deliveries')
            ->join('devices', 'devices.id', '=', 'alert_deliveries.device_id')
            ->join('alerts', 'alerts.id', '=', 'alert_deliveries.alert_id')
            ->where('devices.user_id', $request->user()->id)
            ->where('alerts.sent_at', '>=', $fromDate)
            ->select(
                'devices.id',
                'devices.name',
                'devices.type',
                'devices.location',
                DB::raw('COUNT(*) as total_alerts'),
                DB::raw('SUM(CASE WHEN alert_deliveries.status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN alert_deliveries.status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('devices.id', 'devices.name', 'devices.type', 'devices.location')
            ->orderByDesc('total_alerts')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $topDevices
        ]);
    }
}
