<?php

namespace CarroPublic\EventEmitter\ServiceProviders;

use CarroPublic\EventEmitter\Subscribers\JobsSubscriber;
use CarroPublic\EventEmitter\Subscribers\EventSubscriber;

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
