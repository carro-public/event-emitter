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
        foreach (config('event-emitter.eloquents', []) as $eloquent => $eloquentEvents) {
            foreach ($eloquentEvents as $event => $destinations) {
                $qualifiedEventName = "eloquent.{$event}: {$eloquent}";
                $events->listen($qualifiedEventName, function ($model) use ($qualifiedEventName, $destinations) {
                    # If we should not handle the listener, return immediately
                    # This flag will be turned on to prevent echoing
                    if (static::$shouldSkipHandling) {
                        return;
                    }
                    
                    foreach ($destinations as $destination) {
                        EloquentEventEmitter::dispatch($model, $qualifiedEventName)->onConnection($destination);
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
