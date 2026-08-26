<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OperationalNotificationService;
use Illuminate\Console\Command;

class SendWeeklyReports extends Command
{
    protected $signature = 'notifications:weekly-report';

    protected $description = 'Envia o relatório semanal aos usuários que habilitaram essa preferência';

    public function handle(OperationalNotificationService $notifications): int
    {
        $from = now()->subWeek()->startOfWeek();
        $to = $from->copy()->endOfWeek();
        $sent = 0;

        User::query()
            ->where('status', 'active')
            ->whereHas('settings', fn ($query) => $query->where('notify_weekly_report', true))
            ->chunkById(100, function ($users) use ($notifications, $from, $to, &$sent) {
                foreach ($users as $user) {
                    if ($notifications->weeklyReport($user, $from, $to)) {
                        $sent++;
                    }
                }
            });

        $this->info("{$sent} relatório(s) semanal(is) enviado(s).");

        return self::SUCCESS;
    }
}
