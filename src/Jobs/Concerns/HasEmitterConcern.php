<?php

namespace CarroPublic\EventEmitter\Jobs\Concerns;

trait HasEmitterConcern
{
    /**
     * Print log message
     * @param $message
     * @param $context
     * @return void
     */
    public function log($message, $context)
    {
        if (config('event-emitter.logging', false)) {
            logger()->info("[Event Emitter] {$message}", $context);
        }
    }

    /**
     * @param $instance
     * @param $targetClass
     * @return mixed
     */
    public function convertInstanceTo($instance, $targetClass = null)
    {
        $serializedString = serialize($instance);

        if (is_null($targetClass) && preg_match('/O:\d+:"([^\"]*)"/', $serializedString, $matches)) {
            $originalClass = $matches[1];

            $targetClass = data_get(config('event-emitter.transformers'), $originalClass);
            
            if (empty($targetClass)) {
                throw new \InvalidArgumentException("Class {$originalClass} Not Found. Should Use Transformation");
            }
        }
        
        # Unserialize the object again with replaced className
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            strlen($targetClass),
            $targetClass,
            strstr(strstr($serializedString, '"'), ':')
        ));
    }
}
