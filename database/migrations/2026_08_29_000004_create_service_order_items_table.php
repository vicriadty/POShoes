<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('service_catalog_id')->constrained('service_catalogs')->restrictOnDelete();
            $table->string('service_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('price_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('price_approved_at')->nullable();
            $table->timestamps();

            $table->index(['service_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};
