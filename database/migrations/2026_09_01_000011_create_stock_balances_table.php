<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->bigInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['stock_item_id', 'branch_id'], 'stock_balances_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
