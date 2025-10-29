<?php

declare(strict_types=1);

namespace B7s\QueueFlow;

use Closure;
use DateTimeInterface;
use DateInterval;
use InvalidArgumentException;
use B7s\QueueFlow\Services\QueueConfigurationService;
use B7s\QueueFlow\Services\JobDispatcherService;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\App;
use function collect;

class Queue
{
    protected QueueConfigurationService $configService;
    protected JobDispatcherService $dispatcherService;
    /** @var array<int, Closure> */
    protected array $pendingCallbacks = [];
    protected bool $autoDispatchEnabled = false;

    public function __construct()
    {
        $this->configService = new QueueConfigurationService();
        $this->dispatcherService = new JobDispatcherService($this->configService);
        $this->autoDispatchEnabled = config('queue-flow.auto_dispatch', false);
    }

    public static function make(): self
    {
        return App::make(self::class);
    }

    /**
     * Add one or many closures to be executed in the queue
     *
     * @param  Closure|array<int, Closure>  $callbacks
     */
    public function add(Closure|array $callbacks): self
    {
        $normalizedCallbacks = $this->normalizeCallbacks($callbacks);

        if ($normalizedCallbacks === []) {
            throw new InvalidArgumentException('Queue::add expects at least one Closure instance.');
        }

        $this->pendingCallbacks = array_merge($this->pendingCallbacks, $normalizedCallbacks);

        return $this;
    }

    public function autoDispatch(bool $enabled = true): self
    {
        $this->autoDispatchEnabled = $enabled;
        return $this;
    }

    /**
     * Set the queue name
     */
    public function onQueue(string $queue): self
    {
        $this->configService->setQueue($queue);
        return $this;
    }

    /**
     * Set the connection name
     */
    public function onConnection(string $connection): self
    {
        $this->configService->setConnection($connection);
        return $this;
    }

    /**
     * Set delay for the job
     */
    public function delay(DateTimeInterface|DateInterval|int $delay): self
    {
        $this->configService->setDelay($delay);
        return $this;
    }

    /**
     * Prevent relations from being serialized
     */
    public function withoutRelations(): self
    {
        $this->configService->setWithoutRelations(true);
        return $this;
    }

    /**
     * Make the job unique
     */
    public function shouldBeUnique(?int $uniqueFor = null): self
    {
        $this->configService->setShouldBeUnique(true);
        $this->configService->setUniqueFor($uniqueFor ?? config('queue-flow.unique_for', 3600));
        return $this;
    }

    /**
     * Make the job unique until processing
     */
    public function shouldBeUniqueUntilProcessing(): self
    {
        $this->configService->setShouldBeUniqueUntilProcessing(true);
        return $this;
    }

    /**
     * Encrypt the job payload
     */
    public function shouldBeEncrypted(): self
    {
        $this->configService->setShouldBeEncrypted(true);
        return $this;
    }

    /**
     * Apply rate limiting to the job
     */
    public function rateLimited(string $limiterName = 'default'): self
    {
        $this->configService->setRateLimiter($limiterName);
        return $this;
    }

    /**
     * Set a callback to be executed when the job fails
     */
    public function onFailure(Closure $callback): self
    {
        $this->configService->setFailureCallback($callback);
        return $this;
    }

    /**
     * Dispatch the job to the queue
     *
     * @return PendingDispatch|array<int, PendingDispatch>
     */
    public function dispatch(): PendingDispatch|array
    {
        if ($this->pendingCallbacks === []) {
            throw new \RuntimeException('No callback has been added to the queue. Use add() method first.');
        }

        $dispatches = [];

        foreach ($this->pendingCallbacks as $callback) {
            $dispatches[] = $this->dispatcherService->dispatch($callback);
        }

        // Reset state after dispatch
        $this->reset();

        return count($dispatches) === 1 ? $dispatches[0] : $dispatches;
    }

    /**
     * Magic method to automatically dispatch when the object is used in a context that expects a value
     */
    public function __destruct()
    {
        if ($this->pendingCallbacks !== [] && $this->autoDispatchEnabled) {
            $this->dispatch();
        }
    }

    /**
     * Reset the queue configuration
     */
    protected function reset(): void
    {
        $this->pendingCallbacks = [];
        $this->configService->reset();
        $this->autoDispatchEnabled = config('queue-flow.auto_dispatch', false);
    }

    /**
     * @param  Closure|array<int, Closure>  $callbacks
     * @return array<int, Closure>
     */
    protected function normalizeCallbacks(Closure|array $callbacks): array
    {
        if ($callbacks instanceof Closure) {
            return [$callbacks];
        }

        return collect($callbacks)
            ->filter(fn ($callback): bool => $callback instanceof Closure)
            ->values()
            ->all();
    }
}
