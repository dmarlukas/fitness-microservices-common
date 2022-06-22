<?php

namespace App\Exceptions;

use Throwable;
use Exception;

class CMSAPINonExistentException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('Couldn\'t contact CMS API.', $code, $previous);
    }
}
