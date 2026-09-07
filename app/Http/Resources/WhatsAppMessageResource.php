<?php

namespace App\Http\Resources;

use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsAppMessage
 */
class WhatsAppMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'service_order_id' => $this->service_order_id,
            'message_type' => $this->message_type,
            'template_name' => $this->template_name,
            'recipient_phone' => $this->recipient_phone,
            'provider_message_id' => $this->provider_message_id,
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
