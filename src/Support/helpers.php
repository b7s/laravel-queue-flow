<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;

if (! function_exists('queue_flow')) {
    /**
     * Create a new QueueFlow job builder from closures.
     *
     * @param  Closure|array<int, Closure>  $callbacks
     */
    function queue_flow(Closure|array $callbacks, ?bool $autoDispatch = null): Queue
    {
        $queue = Queue::make()
            ->autoDispatch(false)
            ->add($callbacks);

        if ($autoDispatch === null) {
            $autoDispatch = config('queue-flow.auto_dispatch_on_queue_flow_helper');
        }

        if ($autoDispatch) {
            $queue->dispatch();
        }

        return $queue;
    }
}
