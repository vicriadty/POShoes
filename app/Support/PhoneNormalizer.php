<?php

namespace App\Support;

/**
 * Normalisasi nomor WhatsApp ke format internasional 62xxxx (ADR-0001 D6).
 *
 * Aturan:
 * - Hapus semua karakter non-digit.
 * - "0" di depan (format lokal Indonesia) diganti menjadi "62".
 * - "+62", "62", atau "8xx" dinormalisasi ke "62xx".
 * - Hasil selalu unik untuk satu pelanggan aktif (unique index phone_wa_normalized).
 */
final class PhoneNormalizer
{
    /**
     * Normalisasi untuk penyimpanan & pencocokan unik.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        // "+62..." / "62..." sudah internasional.
        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        // "0..." lokal Indonesia → ganti 0 dengan 62.
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        // "8..." telanjang → tambah prefix 62.
        return '62'.$digits;
    }

    /**
     * Format display internasional "+62 xxx xxxx xxxx" (ringan, tanpa library).
     */
    public static function displayInternational(string $normalized): string
    {
        $n = preg_replace('/\D+/', '', $normalized) ?? '';
        if ($n === '' || ! str_starts_with($n, '62')) {
            return $n;
        }

        $national = substr($n, 2);
        if ($national === '') {
            return '+62';
        }

        $chunks = [];
        $offset = 0;
        foreach ([3, 4, 4] as $len) {
            $chunks[] = substr($national, $offset, $len);
            $offset += $len;
        }

        return '+62 '.trim(implode(' ', array_filter($chunks)));
    }
}
