<?php

namespace Core\Exceptions;

use Exception;

class HttpException extends Exception
{
    protected int $statusCode;

    public function __construct(int $statusCode = 500, string $message = '', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;

        if (empty($message)) {
            $message = match ($statusCode) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                422 => 'Unprocessable Entity',
                500 => 'Internal Server Error',
                default => 'HTTP Error ' . $statusCode,
            };
        }

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
