<?php

namespace App\Jobs;

use App\Domain\Messaging\Actions\SendWhatsAppMessage;
use App\Domain\Messaging\Enums\WhatsAppMessageStatus;
use App\Domain\Messaging\Exceptions\MessagingSendException;
use App\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Queue job pengiriman pesan WhatsApp (PRD 7.10, 7.11).
 */
class ProcessWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public int $tries = 4;

    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly int $messageId,
        public readonly string $languageCode,
    ) {}

    public function handle(): void
    {
        $message = WhatsAppMessage::query()->findOrFail($this->messageId);

        if (in_array($message->status->value, ['sent', 'delivered', 'read'], true)) {
            return; // idempotent — jangan kirim ulang
        }

        $message->attempts = $this->attempts();
        $message->save();

        try {
            SendWhatsAppMessage::sendNow($message, $this->languageCode);
        } catch (MessagingSendException $e) {
            if (! $e->isRetryable()) {
                // Non-retryable (4xx) — release bukan retry; tandai failed permanent.
                $this->fail($e);
                $message->failed_at = now();
                $message->status = WhatsAppMessageStatus::Failed;
                $message->save();
                throw $e;
            }

            throw $e; // retryable — job retry dengan backoff
        }
    }

    public function failed(Throwable $e): void
    {
        $message = WhatsAppMessage::find($this->messageId);
        if ($message === null) {
            return;
        }

        $message->failed_at = now();
        $message->status = WhatsAppMessageStatus::Failed;
        $message->error_message = mb_substr($e->getMessage(), 0, 500);
        if ($e instanceof MessagingSendException && $e->providerCode !== null) {
            $message->error_code = $e->providerCode;
        }
        $message->save();
    }
}
