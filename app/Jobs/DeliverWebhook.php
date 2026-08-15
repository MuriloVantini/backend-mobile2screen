<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 20;

    /**
     * @param  array{id: string, event: string, occurred_at: string, data: array<string, mixed>}  $payload
     */
    public function __construct(
        public int $webhookId,
        public array $payload,
        public bool $force = false,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (! $webhook || (! $webhook->is_active && ! $this->force)) {
            return;
        }

        $body = json_encode(
            $this->payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
        $signature = hash_hmac('sha256', $body, (string) $webhook->secret);

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'User-Agent' => 'Mobile2Screen-Webhooks/1.0',
                    'X-Mobile2Screen-Delivery' => $this->payload['id'],
                    'X-Mobile2Screen-Event' => $this->payload['event'],
                    'X-Mobile2Screen-Signature' => "sha256={$signature}",
                ])
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $this->recordAttempt(
                $webhook,
                $response->status(),
                Str::limit($response->body(), 10000, ''),
                $response->successful() ? null : "Resposta HTTP {$response->status()}",
            );

            if (! $response->successful()) {
                throw new RuntimeException("Webhook retornou HTTP {$response->status()}");
            }
        } catch (ConnectionException $exception) {
            $this->recordAttempt($webhook, null, null, $exception->getMessage());
            throw $exception;
        } catch (Throwable $exception) {
            if (! $exception instanceof RuntimeException) {
                $this->recordAttempt($webhook, null, null, $exception->getMessage());
            }

            throw $exception;
        }
    }

    private function recordAttempt(
        Webhook $webhook,
        ?int $responseStatus,
        ?string $responseBody,
        ?string $errorMessage,
    ): void {
        WebhookLog::create([
            'webhook_id' => $webhook->id,
            'event_type' => $this->payload['event'],
            'payload' => $this->payload,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
        ]);

        $webhook->forceFill(['last_triggered' => now()])->save();
    }
}
