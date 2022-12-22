<?php

namespace CarroPublic\EventEmitter\Subscribers;

use CarroPublic\EventEmitter\Jobs\LaravelEventEmitter;
use CarroPublic\EventEmitter\Jobs\EloquentEventEmitter;

class EventSubscriber
{
    static bool $shouldSkipHandling = false;
    
    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     * @return void
     */
    public function subscribe($events)
    {
        # If emitter should be disabled, do not register any listener
        if (config('event-emitter.enable') === false) {
            return;
        }
        
        foreach (config('event-emitter.eloquents', []) as $eloquent => $eloquentEvents) {
            foreach ($eloquentEvents as $event => $config) {
                $qualifiedEventName = "eloquent.{$event}: {$eloquent}";
                $events->listen($qualifiedEventName, function ($model) use ($qualifiedEventName, $config) {
                    # If we should not handle the listener, return immediately
                    # This flag will be turned on to prevent echoing
                    if (static::$shouldSkipHandling) {
                        return;
                    }
                    
                    # If $config is one-dimension array, it is destinations array, with empty options
                    # Otherwise, destinations and options will be defined explicitly
                    if (isset($config['destinations'])) {
                        $destinations = data_get($config['destinations'], []);
                        $options = data_get($config['options'], []);
                    } else {
                        $destinations = $config;
                        $options = [];
                    }
                    
                    foreach ($destinations as $destination) {
                        if (data_get($options, 'afterResponse', false)) {
                            app()->terminating(function () use ($model, $qualifiedEventName, $options, $destination) {
                                EloquentEventEmitter::dispatch($model, $qualifiedEventName, $options)->onConnection($destination);
                            });
                        } else {
                            EloquentEventEmitter::dispatch($model, $qualifiedEventName, $options)->onConnection($destination);
                        }
                    }
                });
            }
        }

        foreach (config('event-emitter.events', []) as $eventName => $destinations) {
            $events->listen($eventName, function ($event) use ($destinations) {
                # If we should not handle the listener, return immediately
                # This flag will be turned on to prevent echoing
                if (static::$shouldSkipHandling) {
                    return;
                }

                foreach ($destinations as $destination) {
                    LaravelEventEmitter::dispatch($event)->onConnection($destination);
                }
            });
        }
    }
}
