<?php

namespace Fitness\MSCommon\Exceptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class InvalidAccessTokenException extends Exception implements HttpExceptionInterface
{
    use Httpable;

    public function __construct($message = "Access token is invalid")
    {
        $this->message = $message;
    }

    public function getStatusCode() : int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
