<?php

namespace App\Domains\CartProduct\Exceptions;

use LogicException;

class InactiveVariantException extends LogicException
{
    public function __construct(string $variantName)
    {
        $message = "Product variant \"{$variantName}\" is no longer available.";

        parent::__construct($message);
    }
}
