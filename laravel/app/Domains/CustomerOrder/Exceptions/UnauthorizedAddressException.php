<?php

namespace App\Domains\CustomerOrder\Exceptions;

use Exception;

class UnauthorizedAddressException extends Exception
{
    public function __construct()
    {
        parent::__construct('The selected address does not belong to this customer.');
    }
}
