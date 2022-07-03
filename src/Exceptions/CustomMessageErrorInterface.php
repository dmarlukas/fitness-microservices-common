<?php

namespace Fitness\MSCommon\Exceptions;

interface CustomMessageErrorInterface
{
    /**
     * Returns the custom message.
     *
     * @return string A custom error message that gets returned to the user
     */
    public function getCustomMessage(): string;
}
