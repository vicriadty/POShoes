<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shoe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('material')->nullable();
            $table->text('condition_summary')->nullable();
            $table->text('customer_description')->nullable();
            $table->text('internal_description')->nullable();
            $table->timestamps();

            $table->index('service_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shoe_items');
    }
};
