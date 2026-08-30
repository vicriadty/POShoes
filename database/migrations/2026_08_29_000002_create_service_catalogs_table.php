<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('category_id')->constrained('service_categories')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->unsignedInteger('estimated_duration_minutes');
            $table->boolean('requires_before_after_photo')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalogs');
        Schema::dropIfExists('service_categories');
    }
};
