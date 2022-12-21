<?php

namespace CarroPublic\EventEmitter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use CarroPublic\EventEmitter\Jobs\Concerns\HasEmitterConcern;

class EloquentEventEmitter implements ShouldQueue
{
    use Dispatchable, Queueable, HasEmitterConcern;
    
    protected $model;
    
    protected $event;

    /**
     * NOTE: The construction of the Job will be created in Source Service
     * @param $model
     * @param $event
     */
    public function __construct($model, $event)
    {
        $this->model = $model;
        $this->event = $event;
    }

    /**
     * NOTE: The handle() of the Job will be handled in Destination Service
     * @return void
     */
    public function handle()
    {
        $this->model = $this->transformObject();

        /** @var Model $model */
        if ($this->model) {
            event($this->event, $this->model);
            $this->log("Received Eloquent Event", [
                'id' => $this->model->id,
                'class' => get_class($this->model),
                'event' => $this->event,
            ]);
        } else {
            $this->log("Received Invalid Eloquent Event", [
                'model' => json_encode($this->model, JSON_PRETTY_PRINT),
            ]);
        }
    }

    /**
     * @return mixed|void
     */
    private function transformObject()
    {
        if ($this->model instanceof \__PHP_Incomplete_Class) {
            $className = data_get(config('event-emitter.transformers', []), $this->getOriginalClassFromEvent());

            # Convert event name from Source Class to Destination Class
            $this->event = str_replace($this->getOriginalClassFromEvent(), $className, $this->event);

            return $this->convertInstanceTo($this->model, $className);
        }

        return $this->model;
    }

    private function getOriginalClassFromEvent()
    {
        preg_match('/eloquent..*: (.*)/', $this->event, $matches);

        return data_get($matches, 1);
    }
}
