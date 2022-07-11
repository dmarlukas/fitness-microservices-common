<?php

// Credit to https://jessarcher.com/blog/httpable-exceptions/ for this idea

namespace Fitness\MSCommon\Exceptions;

use Illuminate\Foundation\Application;

trait Httpable
{
    public function report(Application $app)
    {
        // Report only when running in a queued job or scheduled task.
        return $app->runningInConsole();
    }

    public function getStatusCode() : int
    {
        return $this->statusCode ?? 500;
    }

    public function getHeaders() : array
    {
        return $this->headers ?? [];
    }
}
