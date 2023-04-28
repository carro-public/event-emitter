<?php

return [
    /**
     * Event emitter source name to be used when logging
     */
    'source' => env('EVENT_EMITTER_SOURCE', env('APP_NAME', 'undefined')),

    /**
     * Whether we should enable the event-emitter
     * If false, the EventSubscriber will be disabled
     */
    'enable' => env('EVENT_EMITTER_ENABLE', true),

    /**
     * Whether to allow preserving authenticated user when emitting the event
     */
    'auth' => env('EVENT_EMITTER_AUTHENTICATION', false),
    
    /**
     * Whether to print out the event emitter logs
     */
    'logging' => env('EVENT_EMITTER_LOGGING_ENABLE', false),
    
    /**
     * All of sendable queue destinations
     */
    'queues' => [
        
    ],

    /**
     * Add more configuration for connection for queue config (Redis Driver)
     */
    'connections' => [
        
    ],

    /**
     * All of Eloquent along with Eloquent Events should listen to, and destination queue to emit to
     */
    'eloquents' => [
        
    ],

    /**
     * * All of Laravel Events should listen to, and destination queue to emit to
     */
    'events' => [
        
    ],

    /**
     * To transform the Eloquent Class from Source Emitter to Destination Receiver
     * A => B (Same meaning but different qualified class name)
     * A is Class Name from of Source Emitter
     * B is Class Name in Destination Receiver
     */
    'transformers' => [
        
    ]
];
