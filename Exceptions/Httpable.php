<?php

// Credit to https://jessarcher.com/blog/httpable-exceptions/ for this idea

namespace App\Exceptions;

use Illuminate\Foundation\Application;

trait Httpable
{
    public function report(Application $app)
    {
        // Report only when running in a queued job or scheduled task.
        return $app->runningInConsole();
    }

    public function getStatusCode()
    {
        return $this->statusCode ?? 500;
    }

    public function getHeaders()
    {
        return $this->headers ?? [];
    }
}
