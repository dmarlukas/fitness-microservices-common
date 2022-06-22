<?php

namespace App\Exceptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;

class UpstreamAPINonExistentException extends Exception implements HttpExceptionInterface {
    use Httpable;
    protected $message = 'CMS_API_ENDPOINT not set';
    public function getStatusCode()
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
