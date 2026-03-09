<?php

namespace App\Http\Api\Controllers;

use App\Http\Requests\DailyStatisticsRequest;
use App\Models\StatisticDaily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
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
            'success' => true,
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
            'success' => true,
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
            'success' => true,
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
            'success' => true,
            'data' => $topDevices
        ]);
    }
}
