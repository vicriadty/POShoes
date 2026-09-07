<?php

namespace App\Domain\Messaging\Exceptions;

use RuntimeException;

/**
 * Kegagalan pengiriman pesan dari provider.
 *
 * Retryable bila transient (429/5xx); non-retryable bila 4xx.
 */
class MessagingSendException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $providerCode = null,
        public readonly bool $retryable = true,
    ) {
        parent::__construct($message);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
