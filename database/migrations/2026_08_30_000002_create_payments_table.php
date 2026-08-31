<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->string('payment_number')->unique();
            $table->bigInteger('amount'); // integer rupiah; negatif untuk refund
            $table->timestamp('received_at');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('refunded_from')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['service_order_id', 'voided_at']);
            $table->index('payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
