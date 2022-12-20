<?php

namespace Fitness\MSCommon\Helpers;

use Exception;
use Illuminate\Support\Facades\Http;

class ServerNotificationLogger
{
    const ENV_VAR = 'SUBSCRIPTION_SLACK_ENDPOINT';

    private string $envVar;

    public function __construct(?string $slackEndpointEnvVar = null) {
        $this->envVar = $slackEndpointEnvVar ?? self::ENV_VAR;
    }

    public function log($message, $notifySlack = false)
    {
        $this->logMessage($message);
        if ($notifySlack) {
            $this->sendSlackMessage($message);
        }
    }

    private function sendSlackMessage($message)
    {
        $slackEndpoint = getenv($this->envVar);

        if (!is_string($slackEndpoint)) {
            $this->logMessage("Env {$this->envVar} not set");
            return;
        }

        Http::post($slackEndpoint, [
            'text' => $message
        ]);
    }

    private function logMessage($message) {
        // Wrap in a try catch block in case the log file has had its permissions changed or can't be accessed somehow
        try {
            Logger($message);
        } catch (Exception $e) {

        }
    }
}
