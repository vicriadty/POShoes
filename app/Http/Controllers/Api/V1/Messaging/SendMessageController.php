<?php

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Domain\Messaging\Actions\SendWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsAppMessageResource;
use App\Models\ServiceOrder;
use App\Models\WhatsAppMessage;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengiriman pesan terkait order (invoice/status/reminder).
 */
class SendMessageController extends Controller
{
    public function sendInvoice(Request $request, ServiceOrder $order): JsonResponse
    {
        $customer = $order->customer;

        if ($customer === null || $customer->phone_wa_normalized === '') {
            return response()->json(['message' => 'Customer belum memiliki nomor WhatsApp valid.'], 422);
        }

        $message = SendWhatsAppMessage::dispatch(
            $customer->phone_wa_normalized,
            'invoice',
            (string) config('messaging.templates.invoice.name'),
            (string) config('messaging.templates.invoice.language'),
            ['order_number' => $order->order_number],
            $customer->id,
            $order->id,
        );

        $order->invoices()->latest()->first()?->update(['sent_at' => now()]);

        return ApiResponse::created(new WhatsAppMessageResource($message));
    }

    public function resend(Request $request, WhatsAppMessage $message): JsonResponse
    {
        $sent = in_array($message->status->value, ['sent', 'delivered', 'read'], true);
        if ($sent) {
            return response()->json(['message' => 'Pesan sudah terkirim; gunakan kirim ulang dengan pesan baru.'], 409);
        }

        $language = $message->payload['language'] ?? 'id';
        $new = SendWhatsAppMessage::dispatch(
            $message->recipient_phone,
            $message->message_type,
            (string) $message->template_name,
            $language,
            $message->payload['parameters'] ?? [],
            $message->customer_id,
            $message->service_order_id,
        );

        return ApiResponse::created(new WhatsAppMessageResource($new));
    }
}
