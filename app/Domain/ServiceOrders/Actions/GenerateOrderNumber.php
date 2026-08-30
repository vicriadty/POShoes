<?php

namespace App\Domain\ServiceOrders\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Menghasilkan nomor order unik dan mudah dibaca (PRD §488).
 *
 * Format: SO-YYYYMMDD-XXXX (sesuai preferensi owner).
 * Bagian XXXX adalah urutan per hari (0001, 0002, …). Locking berbasis baris
 * pada tabel service_orders dengan order_number prefix mencegah race condition.
 */
final class GenerateOrderNumber
{
    public static function generate(?\DateTimeInterface $forDate = null): string
    {
        $date = $forDate ?? now();
        $prefix = 'SO-'.$date->format('Ymd').'-';

        // Ambil nomor terakhir dengan prefix hari ini, kunci baris untuk mencegah duplikasi.
        $last = DB::table('service_orders')
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
