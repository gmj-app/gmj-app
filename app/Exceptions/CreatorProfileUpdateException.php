<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class CreatorProfileUpdateException extends RuntimeException
{
    public function __construct(public readonly string $stage, Throwable $previous)
    {
        parent::__construct($previous->getMessage(), (int) $previous->getCode(), $previous);
    }
}
