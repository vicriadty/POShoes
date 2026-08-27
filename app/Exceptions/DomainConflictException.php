<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Konflik state / aturan bisnis yang tidak valid.
 *
 * Dipetakan ke HTTP 409 oleh ApiExceptionHandler. Dipakai untuk transisi status
 * invalid, saldo stok negatif, guard pickup, dsb.
 */
class DomainConflictException extends RuntimeException {}
