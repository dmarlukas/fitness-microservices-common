<?php

namespace Fitness\MSCommon\Models;

use Illuminate\Support\Facades\Config;

class Environment
{
    // These values are what the environment variable APP_ENV is set too.
    // In dev local that's set in the .env file.
    // For azure they are set App services configuration
    const PRODUCTION = 'production';
    const STAGING = 'staging';
    // Dev & Local dev are set to 'local'
    const LOCAL = 'local';

    private string $environment;

    public function __construct()
    {
        // If the APP_ENV variable isn't found we're defaulting to PROD as its safer
        // This class is used to determine whether to give users a free subscription on sign up (amoung other things)
        // used for testing in stg & dev. We don't want to run the risk of this defaulting to LOCAL in prod
        // and live users are given free subscriptions.
        $envVariable = getenv('APP_ENV');
        if (is_string($envVariable)) {
            $this->environment = $envVariable;
        } else {
            $this->environment = Environment::PRODUCTION;
        }
    }

    static function current(): Environment {
        return new Environment();
    }

    public function description(): string {
        return $this->environment;
    }

    public function isProduction(): bool
    {
        return $this->environment == Environment::PRODUCTION;
    }

    public function isStaging(): bool
    {
        return $this->environment == Environment::STAGING;
    }

    public function isDevelopment(): bool
    {
        return $this->environment == Environment::LOCAL;
    }
}
