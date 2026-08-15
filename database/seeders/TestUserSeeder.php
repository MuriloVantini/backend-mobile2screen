<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\ApiKey;
use App\Models\Device;
use App\Models\Plan;
use App\Models\StatisticDaily;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserSession;
use App\Models\UserSetting;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $upsertById = function (string $modelClass, int $id, array $payload): Model {
            /** @var Model|null $model */
            $model = $modelClass::query()->find($id);

            if (! $model) {
                $model = new $modelClass();
                $model->id = $id;
            }

            $model->fill($payload);
            $model->save();

            return $model;
        };

        $ids = [
            'plan' => 2,
            'user' => 1,
            'device' => 1,
            'tag' => 1,
            'alert' => 1,
            'alert_delivery' => 1,
            'api_key' => 1,
            'user_session' => 1,
            'webhook' => 1,
            'webhook_log' => 1,
            'activity_log' => 1,
            'statistic_daily' => 1,
        ];

        $plan = $upsertById(Plan::class, $ids['plan'], Plan::factory()->pro()->make()->getAttributes());

        // 1. Usuario
        $userPayload = User::factory()->make([
            'email' => 'teste@example.com',
            'name' => 'Usuario Teste',
            'password' => 'password',
            'company' => 'Empresa Teste',
            'phone' => '+55 11 99999-0000',
            'plan_id' => $plan->id,
            'status' => 'active',
            'role' => 'admin',
            'last_active' => now(),
        ])->only([
            'name',
            'email',
            'password',
            'company',
            'phone',
            'plan_id',
            'status',
            'role',
            'last_active',
        ]);

        /** @var User $user */
        $user = $upsertById(User::class, $ids['user'], $userPayload);

        // 2. Configuracoes do usuario (1:1)
        $userSettingPayload = UserSetting::factory()->make([
            'user_id' => $user->id,
            'notification_email' => $user->email,
            'notification_phone' => $user->phone,
            'theme' => 'dark',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
        ])->getAttributes();

        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            $userSettingPayload
        );

        // 3. Dispositivo
        $devicePayload = Device::factory()->make([
            'user_id' => $user->id,
            'name' => 'TV Recepcao',
            'type' => 'tv',
            'location' => 'Recepcao - Andar 1',
            'ip_address' => '192.168.1.100',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'is_online' => true,
            'last_seen' => now(),
            'metadata' => ['resolution' => '1920x1080', 'firmware' => '1.0.0'],
        ])->getAttributes();

        /** @var Device $device */
        $device = $upsertById(Device::class, $ids['device'], $devicePayload);

        // 4. Tag
        $tagPayload = Tag::factory()->make([
            'user_id' => $user->id,
            'name' => 'Urgente',
            'color' => 'red',
        ])->getAttributes();

        /** @var Tag $tag */
        $tag = $upsertById(Tag::class, $ids['tag'], $tagPayload);

        // 5. Pivot: device_tags
        DB::table('device_tags')->insertOrIgnore([
            'device_id' => $device->id,
            'tag_id' => $tag->id,
            'created_at' => now(),
        ]);

        // 6. Alerta
        $alertPayload = Alert::factory()->make([
            'user_id' => $user->id,
        ])->getAttributes();

        /** @var Alert $alert */
        $alert = $upsertById(Alert::class, $ids['alert'], $alertPayload);

        // 7. Pivot: alert_tags
        DB::table('alert_tags')->insertOrIgnore([
            'alert_id' => $alert->id,
            'tag_id' => $tag->id,
            'created_at' => now(),
        ]);

        // 8. Entrega do alerta (AlertDelivery)
        $deliveryPayload = AlertDelivery::factory()->make([
            'alert_id' => $alert->id,
            'device_id' => $device->id,
        ])->getAttributes();

        /** @var AlertDelivery $alertDelivery */
        $alertDelivery = $upsertById(AlertDelivery::class, $ids['alert_delivery'], $deliveryPayload);

        // 9. API Key
        $apiKeyPayload = ApiKey::factory()->make([
            'user_id' => $user->id,
        ])->getAttributes();

        /** @var ApiKey $apiKey */
        $apiKey = $upsertById(ApiKey::class, $ids['api_key'], $apiKeyPayload);

        // 10. Sessao do usuario
        $userSessionPayload = UserSession::factory()->make([
            'user_id' => $user->id,
        ])->getAttributes();

        /** @var UserSession $userSession */
        $userSession = $upsertById(UserSession::class, $ids['user_session'], $userSessionPayload);

        // 11. Webhook
        $webhookPayload = Webhook::factory()->make([
            'user_id' => $user->id,
        ])->getAttributes();

        /** @var Webhook $webhook */
        $webhook = $upsertById(Webhook::class, $ids['webhook'], $webhookPayload);

        // 12. Log do webhook
        $webhookLogPayload = WebhookLog::factory()->make([
            'webhook_id' => $webhook->id,
        ])->getAttributes();

        /** @var WebhookLog $webhookLog */
        $webhookLog = $upsertById(WebhookLog::class, $ids['webhook_log'], $webhookLogPayload);

        // 13. Log de atividade
        $activityLogPayload = ActivityLog::factory()->make([
            'user_id' => $user->id,
            'resource_id' => $user->id,
        ])->getAttributes();

        /** @var ActivityLog $activityLog */
        $activityLog = $upsertById(ActivityLog::class, $ids['activity_log'], $activityLogPayload);

        // 14. Estatistica diaria
        $statisticDailyPayload = StatisticDaily::factory()->make([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ])->getAttributes();

        /** @var StatisticDaily $statisticDaily */
        $statisticDaily = $upsertById(StatisticDaily::class, $ids['statistic_daily'], $statisticDailyPayload);

        unset($alertDelivery, $apiKey, $userSession, $webhookLog, $activityLog, $statisticDaily);

        $this->command->info('Usuario teste@example.com criado com factories para todas as entidades.');
    }
}
