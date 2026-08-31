<?php

namespace App\Domain\Invoices\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Menghasilkan nomor invoice unik.
 *
 * Format: INV-YYYYMM-XXXX (urutan per bulan). Row-lock pada invoices dengan
 * prefix bulan mencegah duplikasi.
 */
final class GenerateInvoiceNumber
{
    public static function generate(?\DateTimeInterface $forDate = null): string
    {
        $date = $forDate ?? now();
        $prefix = 'INV-'.$date->format('Ym').'-';

        $last = DB::table('invoices')
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
