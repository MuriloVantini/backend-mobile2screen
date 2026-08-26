<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Device;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class OperationalNotificationService
{
    public function alertFailed(User $user, Alert $alert, int $failedDevices): bool
    {
        return $this->send(
            $user,
            'notify_alert_failed',
            new OperationalNotification('alert_failed', 'Falha na entrega de alerta', [
                "O alerta \"{$alert->title}\" não pôde ser entregue a {$failedDevices} dispositivo(s).",
                'Consulte o histórico do Mobile2Screen para verificar os dispositivos afetados.',
            ]),
        );
    }

    public function deviceOffline(Device $device): bool
    {
        $device->loadMissing('user');

        return $device->user !== null && $this->send(
            $device->user,
            'notify_device_offline',
            new OperationalNotification('device_offline', 'Dispositivo desconectado', [
                "O dispositivo \"{$device->name}\" foi marcado como offline.",
                'Verifique a alimentação, a rede e o processo kiosk do dispositivo.',
            ]),
        );
    }

    public function deviceConnected(Device $device): bool
    {
        $device->loadMissing('user');

        return $device->user !== null && $this->send(
            $device->user,
            'notify_device_connected',
            new OperationalNotification('device_connected', 'Dispositivo conectado', [
                "O dispositivo \"{$device->name}\" está online e pronto para receber alertas.",
            ]),
        );
    }

    public function weeklyReport(User $user, CarbonInterface $from, CarbonInterface $to): bool
    {
        $alreadySent = ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'notification.weekly_report')
            ->where('created_at', '>=', now()->startOfWeek())
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $alerts = $user->alerts()
            ->whereBetween('sent_at', [$from, $to])
            ->count();

        $deliveries = AlertDelivery::query()
            ->whereHas('alert', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereBetween('sent_at', [$from, $to]));

        $received = (clone $deliveries)
            ->where(function ($query) {
                $query
                    ->whereNotNull('delivered_at')
                    ->orWhereIn('status', ['delivered', 'acknowledged', 'dismissed']);
            })
            ->count();
        $failed = (clone $deliveries)->where('status', 'failed')->count();
        $onlineDevices = $user->devices()->where('is_online', true)->count();
        $totalDevices = $user->devices()->count();

        $sent = $this->send(
            $user,
            'notify_weekly_report',
            new OperationalNotification('weekly_report', 'Relatório semanal do Mobile2Screen', [
                "Período: {$from->format('d/m/Y')} a {$to->format('d/m/Y')}.",
                "Alertas enviados: {$alerts}.",
                "Entregas recebidas: {$received}. Falhas: {$failed}.",
                "Dispositivos online: {$onlineDevices} de {$totalDevices}.",
            ]),
        );

        if ($sent) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'notification.weekly_report',
                'resource_type' => 'user',
                'resource_id' => $user->id,
                'metadata' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'alerts' => $alerts,
                    'received' => $received,
                    'failed' => $failed,
                ],
            ]);
        }

        return $sent;
    }

    public function alertLimitReached(User $user): bool
    {
        $user->loadMissing('plan');
        $limit = $user->plan?->max_alerts_per_month;

        if (! is_int($limit) || $limit <= 0) {
            return false;
        }

        $monthStart = now()->startOfMonth();
        $current = $user->alerts()->where('sent_at', '>=', $monthStart)->count();
        $threshold = (int) ceil($limit * 0.8);

        if ($current < $threshold) {
            return false;
        }

        $alreadySent = ActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'notification.alert_limit_reached')
            ->where('created_at', '>=', $monthStart)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $sent = $this->send(
            $user,
            'notify_limit_reached',
            new OperationalNotification('alert_limit_reached', 'Limite mensal de alertas próximo', [
                "Sua conta utilizou {$current} de {$limit} alertas disponíveis neste mês.",
                'Considere revisar o uso ou o plano antes de atingir o limite.',
            ]),
        );

        if ($sent) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'notification.alert_limit_reached',
                'resource_type' => 'plan',
                'resource_id' => $user->plan_id,
                'metadata' => [
                    'month' => $monthStart->format('Y-m'),
                    'current' => $current,
                    'limit' => $limit,
                    'threshold' => $threshold,
                ],
            ]);
        }

        return $sent;
    }

    private function send(User $user, string $preference, OperationalNotification $notification): bool
    {
        $settings = $user->settings()->firstOrCreate();

        if (! $settings->{$preference}) {
            return false;
        }

        $recipient = $settings->notification_email ?: $user->email;

        try {
            Notification::route('mail', $recipient)->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar notificação operacional', [
                'kind' => $notification->kind,
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
