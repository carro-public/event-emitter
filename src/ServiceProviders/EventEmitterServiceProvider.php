<?php

namespace CarroPublic\EventEmitter\ServiceProviders;

use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Contracts\Auditor;
use CarroPublic\EventEmitter\Guards\KernelGuard;
use CarroPublic\EventEmitter\Subscribers\JobsSubscriber;
use CarroPublic\EventEmitter\Subscribers\EventSubscriber;
use CarroPublic\EventEmitter\Auditing\Auditor as EventEmitterAuditor;

class EventEmitterServiceProvider extends \Illuminate\Foundation\Support\Providers\EventServiceProvider
{
    protected $subscribe = [
        JobsSubscriber::class,
        EventSubscriber::class,
    ];

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/event-emitter.php' => base_path('config/event-emitter.php')
        ], ['event-emitter']);
        
        $this->mergeConfigFrom(__DIR__.'/../../config/event-emitter.php', 'event-emitter');
        
        $this->registerQueueConfig();

        if ($this->app->runningInConsole()) {
            # Register new Auth Guard for Kernel Application
            Auth::extend('kernel', function () {
                return new KernelGuard();
            });

            # Add new kernel guard to support console job authenticated
            config(['auth.guards.kernel' => [
                'driver' => 'kernel',
                'provider' => 'users'
            ]]);

            # Set kernel guard as default guard when using auth()->user()
            config(['auth.defaults.guard' => 'kernel']);
        }

        $this->registerAuditor();
    }

    protected function registerAuditor()
    {
        $this->app->singleton(Auditor::class, function ($app) {
            return new EventEmitterAuditor($app);
        });
    }

    /**
     * Register Queue Configuration From event-emitter
     * @return void
     */
    protected function registerQueueConfig()
    {
        $queueConfig = config('event-emitter.queues', []);

        foreach ($queueConfig as $queue => $config) {
            config(["queue.connections.{$queue}" => $this->registerConnectionConfig($config)]);
        }
    }

    /**
     * Register Queue Connection From event-emitter
     * @param $config
     * @return mixed
     */
    protected function registerConnectionConfig($config)
    {
        if (data_get($config, 'driver') == 'redis') {
            $connection = data_get($config, 'connection');
            $connectionConfig = config("event-emitter.connections.{$connection}");
            if (empty($connectionConfig)) {
                throw new \InvalidArgumentException("{$connection} connections config is null");
            }

            config(["database.redis.$connection" => $connectionConfig]);
        }
        
        // To support other driver later

        return $config;
    }
}
