<?php

namespace CarroPublic\EventEmitter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use CarroPublic\EventEmitter\Jobs\Concerns\WasAuthenticated;
use CarroPublic\EventEmitter\Jobs\Concerns\HasEmitterConcern;

class LaravelEventEmitter implements ShouldQueue
{
    use Dispatchable, Queueable, HasEmitterConcern, WasAuthenticated;
    
    protected $event;
    
    public function __construct($event)
    {
        $this->loadSource();
        $this->event = $event;

        # Preserve authenticated user who triggered the event if needed
        if (config('event-emitter.auth')) {
            $this->authUser = auth()->user();
        }
    }
    
    public function handle()
    {
        $this->event = $this->transformObject();
        
        /** @var Model $model */
        if ($this->event) {
            $this->log("Received Laravel Event", [
                'class' => get_class($this->event),
                'event' => $this->event,
            ]);
            event($this->event);
            $this->log("Emitted Laravel Event", [
                'class' => get_class($this->event),
                'event' => $this->event,
            ]);
        } else {
            $this->log("Received Invalid Laravel Event", [
                'event' => json_encode($this->event, JSON_PRETTY_PRINT)
            ]);
        }
    }

    /**
     * @return mixed|void
     */
    private function transformObject()
    {
        if ($this->authUser && !($this->authUser instanceof \__PHP_Incomplete_Class)) {
            $this->authUser = $this->convertInstance($this->authUser, config('event-emitter.transformers', []));
        }
        
        return $this->convertInstance($this->event, config('event-emitter.transformers', []));
    }
}
