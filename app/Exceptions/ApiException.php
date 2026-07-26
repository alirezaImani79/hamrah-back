<?php

namespace App\Exceptions;

use App\Support\ErrorCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * An exception that carries a stable {@see ErrorCode} so the exception
 * renderer can surface it as the `code` field of the error envelope.
 *
 * Extends Symfony's HttpException to keep flowing through Laravel's normal
 * exception handling and to retain an explicit HTTP status code.
 */
class ApiException extends HttpException
{
    /**
     * @param  array<string, mixed>|null  $errors  Optional field-keyed error detail.
     */
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message = '',
        public readonly ?array $errors = null,
        int $statusCode = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($statusCode, $message, $previous);
    }
}
