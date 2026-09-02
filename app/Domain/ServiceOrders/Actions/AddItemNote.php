<?php

namespace App\Domain\ServiceOrders\Actions;

use App\Models\ServiceOrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan catatan kerja teknisi pada sebuah item.
 *
 * Memakai kolom `notes` pada service_order_items. Catatan lama tidak dihapus —
 * caller boleh meng-append bila diperlukan; action ini menyimpan nilai baru.
 */
final class AddItemNote
{
    public static function add(ServiceOrderItem $item, string $note, bool $append = false): ServiceOrderItem
    {
        return DB::transaction(function () use ($item, $note, $append) {
            $locked = ServiceOrderItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->notes = $append && $locked->notes
                ? $locked->notes."\n".$note
                : $note;
            $locked->save();

            return $locked;
        });
    }
}
