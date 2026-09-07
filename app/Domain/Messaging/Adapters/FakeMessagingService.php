<?php

namespace App\Domain\Messaging\Adapters;

use App\Domain\Messaging\Contracts\MessagingService;

/**
 * FakeMessagingService untuk development/test (PRD 7.9, 16.11).
 *
 * Tidak memanggil provider; mengembalikan message id deterministik agar
 * alur queue & webhook dapat diuji tanpa koneksi eksternal.
 */
class FakeMessagingService implements MessagingService
{
    public function __construct(
        protected readonly ?string $verifyToken = null,
    ) {}

    public function sendTemplate(
        string $toPhone,
        string $templateName,
        string $languageCode,
        array $parameters = [],
        ?string $apiVersion = null,
    ): array {
        return [
            'provider_message_id' => 'wamid.'.md5($toPhone.'|'.$templateName.'|'.now()->toDateTimeString()),
        ];
    }

    public function verifyWebhook(string $hubMode, string $hubToken, string $hubChallenge): bool
    {
        if ($hubMode !== 'subscribe' || $hubToken === '' || $this->verifyToken === null) {
            return false;
        }

        return hash_equals($this->verifyToken, $hubToken);
    }
}
