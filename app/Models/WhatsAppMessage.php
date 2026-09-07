<?php

namespace App\Models;

use App\Domain\Messaging\Enums\WhatsAppMessageStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'customer_id',
    'service_order_id',
    'message_type',
    'template_name',
    'recipient_phone',
    'provider_message_id',
    'status',
    'payload_hash',
    'payload',
    'sent_at',
    'delivered_at',
    'read_at',
    'failed_at',
    'error_code',
    'error_message',
    'attempts',
])]
class WhatsAppMessage extends Model
{
    use HasFactory;

    /**
     * Nama tabel eksplisit (pluralisasi otomatis salah: what_app_messages).
     */
    protected $table = 'whatsapp_messages';

    protected function casts(): array
    {
        return [
            'status' => WhatsAppMessageStatus::class,
            'payload' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Update status dari webhook (idempotent, tidak mundur).
     */
    public function applyWebhookStatus(?string $providerStatus, ?int $timestamp): void
    {
        $map = [
            'delivered' => WhatsAppMessageStatus::Delivered,
            'read' => WhatsAppMessageStatus::Read,
            'failed' => WhatsAppMessageStatus::Failed,
        ];

        $to = $map[$providerStatus ?? ''] ?? null;
        if ($to === null) {
            return;
        }

        $when = $timestamp !== null ? Carbon::createFromTimestamp($timestamp) : now();

        if ($to === WhatsAppMessageStatus::Delivered && $this->delivered_at === null) {
            $this->delivered_at = $when;
        }
        if ($to === WhatsAppMessageStatus::Read && $this->read_at === null) {
            $this->read_at = $when;
        }
        if ($to === WhatsAppMessageStatus::Failed && $this->failed_at === null) {
            $this->failed_at = $when;
            $this->error_message = $this->error_message ?? 'Webhook status failed';
        }

        $this->status = $to;
        $this->save();
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }
}
