<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Services;

use Closure;
use B7s\QueueFlow\Jobs\QueueFlowJob;
use B7s\QueueFlow\Jobs\UniqueQueueFlowJob;
use B7s\QueueFlow\Jobs\UniqueUntilProcessingQueueFlowJob;
use B7s\QueueFlow\Jobs\EncryptedQueueFlowJob;
use Illuminate\Foundation\Bus\PendingDispatch;

class JobDispatcherService
{
    public function __construct(
        protected QueueConfigurationService $configService
    ) {
    }

    public function dispatch(Closure $callback): PendingDispatch
    {
        $job = $this->createJob($callback);

        $this->applyConfiguration($job);

        if ($failureCallback = $this->configService->getFailureCallback()) {
            $job->withFailureHandler($failureCallback);
        }

        $pendingDispatch = dispatch($job);

        $this->applyRateLimiting($pendingDispatch);

        return $pendingDispatch;
    }

    protected function createJob(Closure $callback): QueueFlowJob
    {
        if ($this->configService->isShouldBeUnique()) {
            return new UniqueQueueFlowJob($callback, $this->configService->getUniqueFor());
        }

        if ($this->configService->isShouldBeUniqueUntilProcessing()) {
            return new UniqueUntilProcessingQueueFlowJob($callback);
        }

        if ($this->configService->isShouldBeEncrypted()) {
            return new EncryptedQueueFlowJob($callback);
        }

        return new QueueFlowJob($callback);
    }

    protected function applyConfiguration(QueueFlowJob $job): void
    {
        if ($queue = $this->configService->getQueue()) {
            $job->onQueue($queue);
        }

        if ($connection = $this->configService->getConnection()) {
            $job->onConnection($connection);
        }

        if ($delay = $this->configService->getDelay()) {
            $job->delay($delay);
        }

        if ($this->configService->shouldWithoutRelations()) {
            $job->withoutRelations();
        }
    }

    protected function applyRateLimiting(PendingDispatch $pendingDispatch): void
    {
        if ($rateLimiter = $this->configService->getRateLimiter()) {
            $pendingDispatch->through([
                new \Illuminate\Queue\Middleware\RateLimited($rateLimiter)
            ]);
        }
    }
}
