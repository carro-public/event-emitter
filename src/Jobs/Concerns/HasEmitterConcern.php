<?php

namespace CarroPublic\EventEmitter\Jobs\Concerns;

use ReflectionClass;

trait HasEmitterConcern
{
    /** @var Emitter source */
    protected $source;

    protected $originUrlData;

    /**
     * Get event emitter source
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get event emitter originUrlData
     * @return array|null
     */
    public function getOriginUrlData()
    {
        return $this->originUrlData;
    }

    /**
     * Load emitter source from config
     * @return string|null
     */
    public function loadSource()
    {
        $this->source = config('event-emitter.source');
        $this->originUrlData = [
            "url" => request()?->fullUrl(),
            "method" => request()?->method(),
        ];
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
                'source' => $source,
            ]);
            logger()->info("[Event Emitter] {$message}", $context);
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

        # Making protected $dates become public $dates to be able to access from outside
        $serializedString = str_replace(
            "s:8:\"\0*\0dates", 's:5:"dates', $serializedString
        );

        return unserialize($serializedString);
    }

    /**
     * Restore the model after serialization.
     *
     * @param  array  $values
     * @return void
     */
    public function __unserialize(array $values)
    {
        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            if (! array_key_exists($name, $values)) {
                continue;
            }

            $property->setAccessible(true);

            $property->setValue(
                $this, $this->convertInstance($values[$name], config('event-emitter.transformers', [])),
            );
        }
    }
}
