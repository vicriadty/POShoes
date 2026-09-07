<?php

namespace App\Domain\Messaging\Contracts;

use App\Domain\Messaging\Exceptions\MessagingSendException;

/**
 * Kontrak layanan pengiriman pesan WhatsApp (PRD 7.9).
 *
 * Provider dipilih via binding di service container; controller/domain tidak
 * pernah memanggil provider langsung.
 */
interface MessagingService
{
    /**
     * Kirim pesan template (business-initiated).
     *
     * @param  array<string, mixed>  $parameters  parameter template (body komponen).
     * @return array{provider_message_id: string}
     *
     * @throws MessagingSendException
     */
    public function sendTemplate(
        string $toPhone,
        string $templateName,
        string $languageCode,
        array $parameters = [],
        ?string $apiVersion = null,
    ): array;

    /**
     * Verifikasi token webhook (verification request).
     */
    public function verifyWebhook(string $hubMode, string $hubToken, string $hubChallenge): bool;
}
