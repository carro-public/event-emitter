<?php

namespace CarroPublic\EventEmitter\Middlewares;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\SendQueuedNotifications;
use CarroPublic\EventEmitter\Jobs\Concerns\WasAuthenticated;

class WasAuthenticateMiddleware
{
    public function handle($command, $next)
    {
        // Login Before Handle
        $authUser = $this->getAuthUser($command);

        // Try to login as last authenticated user when the job was dispatched
        if (! empty($authUser) && $authUser instanceof Authenticatable) {
            auth()->login($authUser);
        }

        $response = $next($command);

        auth()->logout();

        return $response;
    }

    /**
     * @param $command
     * @return null
     */
    public function getAuthUser($command)
    {
        # If the command is to send notification
        if ($command instanceof SendQueuedNotifications && $this->isWasAuthenticated($command->notification)) {
            return $command->notification->getAuthUser();
        }

        # If the command is a generic Queue Job
        if ($this->isWasAuthenticated($command)) {
            return $command->getAuthUser();
        }

        return null;
    }

    protected function isWasAuthenticated($job)
    {
        return in_array(WasAuthenticated::class, class_uses_recursive($job), true);
    }
}
