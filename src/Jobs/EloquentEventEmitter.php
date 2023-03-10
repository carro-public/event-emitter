<?php

namespace CarroPublic\EventEmitter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use CarroPublic\EventEmitter\Jobs\Concerns\WasAuthenticated;
use CarroPublic\EventEmitter\Jobs\Concerns\HasEmitterConcern;

class EloquentEventEmitter implements ShouldQueue
{
    use Dispatchable, Queueable, HasEmitterConcern, WasAuthenticated;

    /**
     * @var Model
     */
    protected $model;

    /**
     * The Qualified Eloquent Event
     * @var string
     */
    protected $event;

    /**
     * Determine whether the eloquent should be refreshed before emitting
     * @var bool
     */
    protected bool $fresh = false;

    /**
     * NOTE: The construction of the Job will be created in Source Service
     * @param $model
     * @param $event
     */
    public function __construct($model, $event, $options = [])
    {
        $this->model = $model;
        $this->event = $event;

        # Preserve authenticated user who triggered the event if needed
        if (config('event-emitter.auth')) {
            $this->authUser = auth()->user();
        }

        # Set Job custom option
        foreach ($options as $option => $value) {
            $this->{$option} = $value;
        }
    }

    /**
     * NOTE: The handle() of the Job will be handled in Destination Service
     * @return void
     */
    public function handle()
    {
        $this->model = $this->transformObject();
        
        # If the Eloquent should be refreshed before emitting
        if ($this->fresh) {
            $this->model = $this->model->fresh();
        }

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
