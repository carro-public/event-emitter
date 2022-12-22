<?php

namespace CarroPublic\EventEmitter\Subscribers;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use CarroPublic\EventEmitter\Jobs\LaravelEventEmitter;
use CarroPublic\EventEmitter\Jobs\EloquentEventEmitter;

class JobsSubscriber
{
    public function onJobStarted(JobProcessing $event)
    {
        // If we are processing a SyncEvent Job, don't need to process the Emitter
        // This is to prevent echoing between two services
        $currentJob = $event->job->payload()['displayName'] ?? '';
        if (in_array($currentJob, [EloquentEventEmitter::class, LaravelEventEmitter::class])) {
            EventSubscriber::$shouldSkipHandling = true;
        }
    }

    public function onJobProcessed(JobProcessed $event)
    {
        // Always set it back to normal behavior
        EventSubscriber::$shouldSkipHandling = false;
    }

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
        
        $events->listen(JobProcessing::class, [self::class, 'onJobStarted']);
        $events->listen(JobProcessed::class, [self::class, 'onJobProcessed']);
    }
}
