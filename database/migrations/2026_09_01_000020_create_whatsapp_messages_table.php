<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('service_order_id')->nullable()->constrained('service_orders')->nullOnDelete();
            $table->string('message_type'); // order_received | invoice | status_update | reminder
            $table->string('template_name')->nullable();
            $table->string('recipient_phone');
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('status')->default('pending'); // pending|sent|delivered|read|failed|cancelled
            $table->string('payload_hash')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['service_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
