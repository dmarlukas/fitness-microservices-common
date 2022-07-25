<?php

namespace Fitness\MSCommon\Exceptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class MissingEnvVarException extends Exception implements HttpExceptionInterface
{
    use Httpable;

    public function __construct($message = "")
    {
        $this->message = $message;
    }

    public function getStatusCode() : int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
