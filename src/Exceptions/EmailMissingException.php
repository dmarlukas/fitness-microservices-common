<?php

namespace Fitness\MSCommon\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Fitness\MSCommon\Exceptions\Httpable;

class EmailMissingException extends Exception implements HttpExceptionInterface, CustomMessageErrorInterface
{
    use Httpable;
    protected $message;
    const EMAIL_MISSING_ERROR_CODE = 420;

    public function __construct($message = 'Unable to retrieve email, please ensure your login method has access to your email.')
    {
        $this->message = $message;
    }

    public function getStatusCode(): int
    {
        return self::EMAIL_MISSING_ERROR_CODE;
    }

    public function getCustomMessage(): string
    {
        return $this->message;
    }
}
