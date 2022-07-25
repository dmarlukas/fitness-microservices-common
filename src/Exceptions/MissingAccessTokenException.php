<?php

namespace Fitness\MSCommon\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class MissingAccessTokenException extends Exception implements HttpExceptionInterface, CustomMessageErrorInterface
{
    use Httpable;

    public function __construct($message = "Expected an Authorization Bearer token to be set, but none present or null value.")
    {
        $this->message = $message;
    }

    public function getStatusCode() : int
    {
        return Response::HTTP_UNAUTHORIZED;
    }

    public function getCustomMessage(): string
    {
        return $this->message;
    }
}
