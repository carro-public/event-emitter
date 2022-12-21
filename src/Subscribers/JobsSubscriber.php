<?php

namespace CarroPublic\EventEmitter\Subscribers;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use CarroPublic\EventEmitter\Jobs\EloquentEventEmitter;

class JobsSubscriber
{
    public function onJobStarted(JobProcessing $event)
    {
        // If we are processing a SyncEvent Job, don't need to process the Emitter
        // This is to prevent echoing between two services
        if ($event->job->payload()['displayName'] ?? '' === EloquentEventEmitter::class) {
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
        $events->listen(JobProcessing::class, [self::class, 'onJobStarted']);
        $events->listen(JobProcessed::class, [self::class, 'onJobProcessed']);
    }
}
