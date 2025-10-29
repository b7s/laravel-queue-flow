<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Services;

use Closure;
use DateInterval;
use DateTimeInterface;

class QueueConfigurationService
{
    protected ?string $queue = null;
    protected ?string $connection = null;
    protected DateTimeInterface|DateInterval|int|null $delay = null;
    protected bool $withoutRelations = false;
    protected bool $shouldBeUnique = false;
    protected bool $shouldBeUniqueUntilProcessing = false;
    protected bool $shouldBeEncrypted = false;
    protected ?string $rateLimiterName = null;
    protected int $uniqueFor;
    protected ?Closure $failureCallback = null;

    public function __construct()
    {
        $this->queue = config('queue-flow.queue');
        $this->connection = config('queue-flow.connection');
        $this->uniqueFor = $this->resolveUniqueFor();
    }

    public function setQueue(?string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function setConnection(?string $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    public function setDelay(DateTimeInterface|DateInterval|int|null $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    public function getDelay(): DateTimeInterface|DateInterval|int|null
    {
        return $this->delay;
    }

    public function setWithoutRelations(bool $withoutRelations): self
    {
        $this->withoutRelations = $withoutRelations;
        return $this;
    }

    public function shouldWithoutRelations(): bool
    {
        return $this->withoutRelations;
    }

    public function setShouldBeUnique(bool $shouldBeUnique): self
    {
        $this->shouldBeUnique = $shouldBeUnique;
        return $this;
    }

    public function isShouldBeUnique(): bool
    {
        return $this->shouldBeUnique;
    }

    public function setShouldBeUniqueUntilProcessing(bool $shouldBeUniqueUntilProcessing): self
    {
        $this->shouldBeUniqueUntilProcessing = $shouldBeUniqueUntilProcessing;
        return $this;
    }

    public function isShouldBeUniqueUntilProcessing(): bool
    {
        return $this->shouldBeUniqueUntilProcessing;
    }

    public function setShouldBeEncrypted(bool $shouldBeEncrypted): self
    {
        $this->shouldBeEncrypted = $shouldBeEncrypted;
        return $this;
    }

    public function isShouldBeEncrypted(): bool
    {
        return $this->shouldBeEncrypted;
    }

    public function setRateLimiter(?string $rateLimiterName): self
    {
        $this->rateLimiterName = $rateLimiterName;
        return $this;
    }

    public function getRateLimiter(): ?string
    {
        return $this->rateLimiterName;
    }

    public function setUniqueFor(int $seconds): self
    {
        $this->uniqueFor = $seconds;
        return $this;
    }

    public function getUniqueFor(): int
    {
        return $this->uniqueFor;
    }

    public function setFailureCallback(?Closure $callback): self
    {
        $this->failureCallback = $callback;
        return $this;
    }

    public function getFailureCallback(): ?Closure
    {
        return $this->failureCallback;
    }

    public function reset(): void
    {
        $this->queue = config('queue-flow.queue');
        $this->connection = config('queue-flow.connection');
        $this->delay = null;
        $this->withoutRelations = false;
        $this->shouldBeUnique = false;
        $this->shouldBeUniqueUntilProcessing = false;
        $this->shouldBeEncrypted = false;
        $this->rateLimiterName = null;
        $this->uniqueFor = $this->resolveUniqueFor();
        $this->failureCallback = null;
    }

    protected function resolveUniqueFor(): int
    {
        $value = config('queue-flow.unique_for');

        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        return 3600;
    }
}
