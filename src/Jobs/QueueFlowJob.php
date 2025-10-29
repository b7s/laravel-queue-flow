<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Jobs;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

class QueueFlowJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected SerializableClosure $callback;
    protected bool $shouldWithoutRelations = false;
    protected ?SerializableClosure $failureHandler = null;

    public function __construct(Closure $callback)
    {
        $this->callback = new SerializableClosure($callback);
    }

    public function handle(): void
    {
        $closure = $this->callback->getClosure();
        $closure();
    }

    public function withoutRelations(): self
    {
        $this->shouldWithoutRelations = true;
        return $this;
    }

    public function withFailureHandler(?Closure $callback): self
    {
        $this->failureHandler = $callback !== null
            ? new SerializableClosure($callback)
            : null;

        return $this;
    }

    public function failed(Throwable $exception): void
    {
        if ($this->failureHandler !== null) {
            ($this->failureHandler->getClosure())($exception);
        }
    }

    public function __serialize(): array
    {
        $data = [
            'callback' => $this->callback,
            'shouldWithoutRelations' => $this->shouldWithoutRelations,
            'failureHandler' => $this->failureHandler,
        ];

        if ($this->shouldWithoutRelations) {
            return $this->getSerializedPropertyValue($data);
        }

        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->callback = $data['callback'];
        $this->shouldWithoutRelations = $data['shouldWithoutRelations'] ?? false;
        $this->failureHandler = $data['failureHandler'] ?? null;
    }
}
