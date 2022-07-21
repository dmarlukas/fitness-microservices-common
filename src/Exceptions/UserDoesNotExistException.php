<?php

namespace Fitness\MSCommon\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Fitness\MSCommon\Exceptions\Httpable;

class UserDoesNotExistException extends Exception implements HttpExceptionInterface, CustomMessageErrorInterface
{
    use Httpable;

    public function __construct($message = "User not found.")
    {
        $this->message = $message;
    }

    public function getStatusCode() : int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getCustomMessage(): string
    {
        return $this->message;
    }
}