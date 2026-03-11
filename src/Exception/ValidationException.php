<?php
declare(strict_types=1);

namespace StoYuristov\Exception;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /**
     * @param string[] $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed: ' . implode(', ', $errors));
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
