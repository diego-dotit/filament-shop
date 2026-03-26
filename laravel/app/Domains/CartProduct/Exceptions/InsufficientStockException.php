<?php

namespace App\Domains\CartProduct\Exceptions;

use LogicException;

class InsufficientStockException extends LogicException
{
    public function __construct(string $variantName, int $requested, int $available)
    {
        $message = "Insufficient stock for product variant \"{$variantName}\": {$requested} requested, {$available} available.";

        parent::__construct($message);
    }
}
