<?php

namespace App\Domains\OrderPlacement\Exceptions;

use LogicException;

class EmptyCartException extends LogicException
{
    public function __construct()
    {
        parent::__construct('Cart is empty; cannot place order.');
    }
}
