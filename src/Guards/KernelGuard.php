<?php

namespace CarroPublic\EventEmitter\Guards;

use InvalidArgumentException;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class KernelGuard implements Guard, StatefulGuard
{
    use GuardHelpers;

    public function user()
    {
        return $this->user;
    }

    public function login(Authenticatable $user, $remember = false)
    {
        $this->user = $user;
    }

    public function logout()
    {
        $this->user = null;
    }

    public function loginUsingId($id, $remember = false)
    {
        $this->user = $this->provider->retrieveById($id);
    }

    public function onceUsingId($id)
    {
        $this->user = $this->provider->retrieveById($id);
    }

    public function validate(array $credentials = [])
    {
        $this->unsupport(__FUNCTION__);
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        $this->unsupport(__FUNCTION__);
    }

    public function once(array $credentials = [])
    {
        $this->unsupport(__FUNCTION__);
    }

    public function viaRemember()
    {
        $this->unsupport(__FUNCTION__);
    }

    /**
     * @param $method
     * @return mixed
     */
    protected function unsupport($method)
    {
        throw new InvalidArgumentException("Unsupport {$method} in Kernel Guard");
    }
}
