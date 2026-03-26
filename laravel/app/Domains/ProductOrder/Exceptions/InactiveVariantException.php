<?php

namespace App\Domains\ProductOrder\Exceptions;

use RuntimeException;

class InactiveVariantException extends RuntimeException
{
    public function __construct(string $message = 'One or more product variants in your cart are no longer active.')
    {
        parent::__construct($message);
    }
}
