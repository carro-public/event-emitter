<?php

namespace CarroPublic\EventEmitter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use CarroPublic\EventEmitter\Jobs\Concerns\HasEmitterConcern;

class LaravelEventEmitter implements ShouldQueue
{
    use Dispatchable, Queueable, HasEmitterConcern;
    
    protected $event;
    
    public function __construct($event)
    {
        $this->event = $event;
    }
    
    public function handle()
    {
        $this->event = $this->transformObject();
        
        /** @var Model $model */
        if ($this->event) {
            event($this->event);
            $this->log("Received Laravel Event", [
                'class' => get_class($this->event),
                'event' => $this->event,
            ]);
        }
    }

    /**
     * @return mixed|void
     */
    private function transformObject()
    {
        if ($this->event instanceof \__PHP_Incomplete_Class) {
            return $this->convertInstanceTo($this->event);
        }
        
        return $this->event;
    }
}
