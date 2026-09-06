<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('proposed_price')->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropColumn('proposed_price');
        });
    }
};
