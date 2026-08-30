<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_shoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_item_id')->constrained('service_order_items')->cascadeOnDelete();
            $table->foreignId('shoe_item_id')->constrained('shoe_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['service_order_item_id', 'shoe_item_id'], 'order_item_shoes_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_shoes');
    }
};
