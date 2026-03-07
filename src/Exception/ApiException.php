<?php
declare(strict_types=1);

namespace StoYuristov\Exception;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(string $message, private readonly int $httpStatusCode = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
