<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Unique Job Duration
    |--------------------------------------------------------------------------
    |
    | When using shouldBeUnique(), this defines the default duration (in seconds)
    | for which the job should remain unique.
    |
    */
    'unique_for' => env('QUEUE_FLOW_UNIQUE_FOR', 3600),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiters
    |--------------------------------------------------------------------------
    |
    | Here you can define rate limiters that can be used with the rateLimited()
    | method. Each limiter should have a key that you'll reference.
    |
    */
    'rate_limiters' => [
        'default' => [
            'limit' => 60,
            'per_minute' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Dispatch
    |--------------------------------------------------------------------------
    |
    | When using queue_flow(), this defines whether the job should be dispatched
    | automatically when the object is used in a context that expects a value.
    |
    | Be careful with this option, if set to true, the job will be dispatched immediately
    | without any configuration applied, like onQueue(), shouldBeUnique(), delay(), etc.
    |
    | Use with caution.
    |
    */
    'auto_dispatch' => env('QUEUE_FLOW_AUTO_DISPATCH', false),
    'auto_dispatch_on_queue_flow_helper' => env('QUEUE_FLOW_AUTO_DISPATCH_ON_HELPER', true),
];
