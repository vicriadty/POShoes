<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('remember_token')->constrained()->nullOnDelete();
            $table->string('phone_wa')->nullable()->after('branch_id');
            $table->string('phone_wa_normalized', 20)->nullable()->after('phone_wa');
            $table->boolean('is_active')->default(true)->after('phone_wa_normalized');

            // Nomor WhatsApp unik di antara user aktif.
            $table->index('phone_wa_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_wa_normalized']);
            $table->dropColumn(['branch_id', 'phone_wa', 'phone_wa_normalized', 'is_active']);
        });
    }
};
