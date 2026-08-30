<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shoe_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shoe_item_id')->constrained('shoe_items')->cascadeOnDelete();
            $table->string('area')->nullable();
            $table->string('defect_type')->nullable();
            $table->string('severity')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('photo_id')->nullable()->constrained('shoe_photos')->nullOnDelete();
            $table->timestamps();

            $table->index('shoe_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shoe_conditions');
    }
};
