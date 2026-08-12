<?php

namespace App\Exceptions;

use Exception;

class BookingAmountMismatchException extends Exception
{
    public function __construct()
    {
        parent::__construct('Total amount mismatch');
    }
}
