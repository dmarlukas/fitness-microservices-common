<?php

namespace App\Exceptions;

use App\Exceptions\Httpable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class IDTokenVerificationException extends Exception implements HttpExceptionInterface
{
    use Httpable;

    public function __construct($message = "")
    {
        $this->message = $message;
    }

    protected $message = 'Expected an IDToken to be set, but none present or null value.';

    public function getStatusCode() {
        return Response::HTTP_UNAUTHORIZED;
    }
}
