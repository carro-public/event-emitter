<?php

namespace CarroPublic\EventEmitter\Jobs\Concerns;

trait HasEmitterConcern
{
    /** @var Emitter source */
    protected $source;

    /**
     * Get event emitter source
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Load emitter source from config
     * @return string|null
     */
    public function loadSource()
    {
        $this->source = config('event-emitter.source');
    }

    /**
     * Print log message
     * @param $message
     * @param $context
     * @return void
     */
    public function log($message, $context)
    {
        if (config('event-emitter.logging', false)) {
            $source = $this->getSource() ?? 'undefined';
            $context = array_merge($context, [
                'authUser' => data_get($this->getAuthUser(), 'id'),
            ]);
            logger()->info("[Event Emitter] Source $source {$message}", $context);
        }
    }

    /**
     * Deep convert class names from event-emitter transformers
     * @param $instance
     * @param $mapping
     * @return mixed
     */
    public function convertInstance($instance, $mapping)
    {
        $serializedString = serialize($instance);

        foreach ($mapping as $old => $new) {
            if (!is_string($new)) {
                continue;
            }
            # Generate new class marker on serialized string
            $oldClassMarker = sprintf('O:%d:"%s"', strlen($old), $old);
            $newClassMarker = sprintf('O:%d:"%s"', strlen($new), $new);
            $serializedString = str_replace($oldClassMarker, $newClassMarker, $serializedString);
        }

        return unserialize($serializedString);
    }
}
