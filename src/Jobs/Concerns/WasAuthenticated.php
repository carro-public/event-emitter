<?php

namespace CarroPublic\EventEmitter\Jobs\Concerns;

use Illuminate\Auth\Authenticatable;
use CarroPublic\EventEmitter\Middlewares\WasAuthenticateMiddleware;

trait WasAuthenticated
{
    /** @var Authenticatable */
    protected $authUser;

    /**
     * The job should go through AuthenticatedMiddleware
     * @return string[]
     */
    public function middleware()
    {
        return [WasAuthenticateMiddleware::class];
    }

    /**
     * Get current authUser
     * @return Authenticatable|null
     */
    public function getAuthUser()
    {
        return $this->authUser ?? null;
    }
}
