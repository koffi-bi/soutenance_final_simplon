<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    protected int $httpStatus;

    public function __construct(string $message, int $httpStatus = 400)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}