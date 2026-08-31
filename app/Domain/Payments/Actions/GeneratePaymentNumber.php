<?php

namespace App\Domain\Payments\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Menghasilkan nomor pembayaran unik & mudah dibaca.
 *
 * Format: PAY-YYYYMMDD-XXXX (urutan per hari). Row-lock pada payments dengan
 * prefix tanggal mencegah duplikasi di bawah concurrency.
 */
final class GeneratePaymentNumber
{
    public static function generate(?\DateTimeInterface $forDate = null): string
    {
        $date = $forDate ?? now();
        $prefix = 'PAY-'.$date->format('Ymd').'-';

        $last = DB::table('payments')
            ->where('payment_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('payment_number')
            ->value('payment_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
