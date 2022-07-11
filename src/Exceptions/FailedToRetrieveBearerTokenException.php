<?php

namespace Fitness\MSCommon\Exceptions;

use Exception;
use Throwable;

class FailedToRetrieveBearerTokenException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('Couldn\'t retrieve a new bearer token.', $code, $previous);
    }
}
