<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Contracts\MessagingService;
use App\Domain\Messaging\Enums\WhatsAppMessageStatus;
use App\Domain\Messaging\Exceptions\MessagingSendException;
use App\Jobs\ProcessWhatsAppMessage;
use App\Models\WhatsAppMessage;

/**
 * Pencatatan + pengiriman pesan WhatsApp (PRD 7.10, 7.11).
 */
final class SendWhatsAppMessage
{
    /**
     * @param  array<int|string, mixed>  $parameters
     */
    public static function dispatch(
        string $toPhone,
        string $messageType,
        string $templateName,
        string $languageCode,
        array $parameters = [],
        ?int $customerId = null,
        ?int $serviceOrderId = null,
    ): WhatsAppMessage {
        $message = WhatsAppMessage::create([
            'customer_id' => $customerId,
            'service_order_id' => $serviceOrderId,
            'message_type' => $messageType,
            'template_name' => $templateName,
            'recipient_phone' => $toPhone,
            'status' => WhatsAppMessageStatus::Pending,
            'payload' => [
                'message_type' => $messageType,
                'template_name' => $templateName,
                'language' => $languageCode,
                'parameters' => $parameters,
            ],
            'attempts' => 0,
        ]);

        ProcessWhatsAppMessage::dispatch($message->id, $languageCode);

        return $message->refresh();
    }

    public static function sendNow(WhatsAppMessage $message, string $languageCode): void
    {
        $service = app(MessagingService::class);

        try {
            $result = $service->sendTemplate(
                $message->recipient_phone,
                (string) $message->template_name,
                $languageCode,
                $message->payload['parameters'] ?? [],
            );
        } catch (MessagingSendException $e) {
            $message->failed_at = now();
            $message->status = WhatsAppMessageStatus::Failed;
            $message->error_code = $e->providerCode;
            $message->error_message = $e->getMessage();
            $message->save();

            throw $e;
        }

        $message->provider_message_id = $result['provider_message_id'];
        $message->status = WhatsAppMessageStatus::Sent;
        $message->sent_at = now();
        $message->save();
    }
}
