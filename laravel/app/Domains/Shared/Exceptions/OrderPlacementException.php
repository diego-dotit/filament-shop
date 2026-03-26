<?php

namespace App\Domains\Shared\Exceptions;

use Exception;

class OrderPlacementException extends Exception
{
    public function __construct(string $message = 'An error occurred while placing the order.')
    {
        parent::__construct($message);
    }
}
