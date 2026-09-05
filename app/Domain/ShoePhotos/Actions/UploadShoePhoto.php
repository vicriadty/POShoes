<?php

namespace App\Domain\ShoePhotos\Actions;

use App\Exceptions\DomainConflictException;
use App\Models\ShoeItem;
use App\Models\ShoePhoto;
use Illuminate\Http\UploadedFile;

/**
 * Simpan foto sepatu (before/during/after) ke disk 'photos'.
 *
 * - Validasi awal dilakukan di FormRequest (MIME, ukuran, type).
 * - File disimpan dengan nama acak; baris shoe_photos dicatat.
 * - Foto before hanya boleh sebelum order mulai diproses; during/after
 *   menyertai pengerjaan (tidak dibatasi ketat di sini — guard alur di
 *   tingkat policy/order-state-machine).
 */
final class UploadShoePhoto
{
    public const ALLOWED_TYPES = ['before', 'during', 'after'];

    public static function upload(
        ShoeItem $shoe,
        UploadedFile $file,
        string $type,
        int $capturedBy,
    ): ShoePhoto {
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw new DomainConflictException("Tipe foto '{$type}' tidak didukung.");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = sprintf(
            '%s-%s-%s.%s',
            $shoe->service_order_id,
            $shoe->id,
            now()->format('YmdHisu'),
            $extension !== '' ? $extension : 'jpg',
        );

        $relativePath = $file->storeAs(
            "shoe_photos/{$type}",
            $filename,
            'photos',
        );

        if ($relativePath === false) {
            throw new \RuntimeException('Gagal menyimpan file foto.');
        }

        return ShoePhoto::create([
            'service_order_id' => $shoe->service_order_id,
            'shoe_item_id' => $shoe->id,
            'type' => $type,
            'file_path' => $relativePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'captured_by' => $capturedBy,
        ]);
    }
}
