<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Jobs;

use Closure;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UniqueQueueFlowJob extends QueueFlowJob implements ShouldBeUnique
{
    public ?int $uniqueFor = null;

    public function __construct(Closure $callback, ?int $uniqueFor = null)
    {
        $uniqueFor ??= (int) config('queue-flow.unique_for', 3600);
        parent::__construct($callback);
        $this->uniqueFor = $uniqueFor;
    }

    /**
     * When a job doesn’t supply its own uniqueId() Laravel falls back to the job’s class name.
     * Because every closure-based QueueFlow job shares the same class,
     * omitting the override would cause all unique jobs to collide and block each other
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return md5(serialize($this->callback));
    }

    public function setUniqueFor(int $seconds): self
    {
        $this->uniqueFor = $seconds;
        return $this;
    }
}
