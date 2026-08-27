<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Input/aturan bisnis yang tidak dapat diproses.
 *
 * Dipetakan ke HTTP 422 oleh ApiExceptionHandler (berbeda dangan ValidationException
 * yang dipakai untuk validasi field form request).
 */
class DomainSystemException extends RuntimeException {}
