<?php

namespace CarroPublic\EventEmitter\Jobs;

use Illuminate\Support\Arr;
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
        $this->loadSource();
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

        if (empty($this->model) || $this->model instanceof \__PHP_Incomplete_Class) {
            $this->log("Failed To Transform Model", [
                'model' => $this->getOriginalClassFromEvent(),
            ]);

            return;
        }
        
        # If the Eloquent should be refreshed before emitting
        if ($this->fresh) {
            $this->model = $this->model->fresh();
        }

        if (empty($this->model)) {
            $this->log("Received Invalid Eloquent Event", [
                'model' => json_encode($this->model, JSON_PRETTY_PRINT),
            ]);

            return;
        }

        event($this->event, $this->model);

        $this->log("Received Eloquent Event", [
            'id' => $this->model->id,
            'class' => $this->model->getMorphClass(),
            'event' => $this->event,
        ]);
    }

    /**
     * Transofrm the model if the model is or include PHP_Incomplete_Class
     *
     * @return mixed
     */
    private function transformObject()
    {
        if (!($this->model instanceof \__PHP_Incomplete_Class)) {
            return $this->convertInstance($this->model, config('event-emitter.transformers', []));
        }

        $obj = $this->transformObjectByDirectMatch();

        if (!empty($obj) && !($obj instanceof \__PHP_Incomplete_Class)) {
            return $obj;
        }

        return $this->transformerObjectByClosure();
    }

    /**
     * Transofrm the model by matching class to class
     *
     * @return mixed
     */
    private function transformObjectByDirectMatch()
    {
        $className = data_get(config('event-emitter.transformers', []), $this->getOriginalClassFromEvent());

        if ($className) {
            # Convert event name from Source Class to Destination Class
            $this->event = str_replace($this->getOriginalClassFromEvent(), $className, $this->event);
        }

        return $this->convertInstance($this->model, config('event-emitter.transformers', []));
    }

    /**
     * Transofrm the model by invoking closure from the transformer config
     *
     * @return mixed
     */
    protected function transformerObjectByClosure() {
        $orgClass = $this->getOriginalClassFromEvent();

        $transformers = config('event-emitter.transformers', []);
        foreach($transformers as $index => $arr) {
            if (!is_array($arr) || !isset($arr['type']) || $arr['type'] != 'closure') {
                continue;
            }

            $obj = $arr['closure']($orgClass, $this->model);

            if (empty($obj) || ($obj instanceof \__PHP_Incomplete_Class)) {
                continue;
            }

            $this->event = str_replace($this->getOriginalClassFromEvent(), get_class($obj), $this->event);

            return $obj;
        }

        return $this->model;
    }

    private function getOriginalClassFromEvent()
    {
        preg_match('/eloquent..*: (.*)/', $this->event, $matches);

        return data_get($matches, 1);
    }
}
