<?php

namespace App\Domain\Invoices\Actions;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;

/**
 * Membuat / mengambil invoice untuk satu order.
 *
 * - Invoice dibuat sekali per order (inv: one-to-many tapi praktiknya satu
 *   invoice aktif per order pada MVP). Jika sudah ada, invoice yang sama
 *   dikembalikan agar tidak menggandakan nomor/record (PRD §7.6 exit criteria:
 *   "Invoice dapat dibuat ulang tanpa menggandakan payment").
 * - status otomatis: paid bila remaining_amount == 0, issued bila masih sisa.
 */
final class GenerateInvoice
{
    public static function for(ServiceOrder $order, ?\DateTimeInterface $dueAt = null): Invoice
    {
        return DB::transaction(function () use ($order, $dueAt) {
            $invoice = Invoice::query()
                ->where('service_order_id', $order->id)
                ->whereIn('status', ['draft', 'issued'])
                ->orderByDesc('id')
                ->first();

            if ($invoice !== null) {
                if ($order->remaining_amount === 0 && $invoice->status !== 'paid') {
                    $invoice->status = 'paid';
                    $invoice->save();
                }

                return $invoice;
            }

            $invoice = Invoice::create([
                'invoice_number' => GenerateInvoiceNumber::generate(),
                'service_order_id' => $order->id,
                'status' => $order->remaining_amount === 0 ? 'paid' : 'issued',
                'issued_at' => now(),
                'due_at' => $dueAt,
            ]);

            return $invoice;
        });
    }
}
