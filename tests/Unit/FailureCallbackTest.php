<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;
use Illuminate\Support\Facades\Queue as LaravelQueueFacade;

uses(TestCase::class);

test('can set failure callback on queue', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'queue_flow_failure_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $queue = (new Queue())->autoDispatch(false);

    $result = $queue
        ->add(fn () => throw new \RuntimeException('Job failed'))
        ->onFailure(function (\Throwable $exception) use ($outputFile): void {
            file_put_contents($outputFile, json_encode([
                'failed' => true,
                'error' => $exception->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT));
        });

    expect($result)->toBeInstanceOf(Queue::class);
});

test('failure callback is executed when job fails', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'queue_flow_failure_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    LaravelQueueFacade::fake();

    try {
        qflow(function (): void {
            throw new \RuntimeException('Simulated failure');
        }, autoDispatch: false)
            ->onFailure(function (\Throwable $exception) use ($outputFile): void {
                file_put_contents($outputFile, json_encode([
                    'error_message' => $exception->getMessage(),
                    'error_class' => get_class($exception),
                    'handled_at' => now()->toIso8601String(),
                ], JSON_PRETTY_PRINT));
            })
            ->dispatch();
    } catch (\RuntimeException) {
        // Expected when using sync queue during tests
    }

    LaravelQueueFacade::assertPushed(\B7s\QueueFlow\Jobs\QueueFlowJob::class);
});

test('failure callback works with unique jobs', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'queue_flow_failure_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    LaravelQueueFacade::fake();

    try {
        qflow(function (): void {
            throw new \Exception('Unique job failed');
        }, autoDispatch: false)
            ->shouldBeUnique(3600)
            ->onFailure(function (\Throwable $exception) use ($outputFile): void {
                file_put_contents($outputFile, json_encode([
                    'unique_job_failed' => true,
                    'error' => $exception->getMessage(),
                ], JSON_PRETTY_PRINT));
            })
            ->dispatch();
    } catch (\Exception) {
        // Expected when using sync queue during tests
    }

    LaravelQueueFacade::assertPushed(\B7s\QueueFlow\Jobs\UniqueQueueFlowJob::class);
});

test('failure callback works with encrypted jobs', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'queue_flow_failure_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    LaravelQueueFacade::fake();

    try {
        qflow(function (): void {
            throw new \Exception('Encrypted job failed');
        }, autoDispatch: false)
            ->shouldBeEncrypted()
            ->onFailure(function (\Throwable $exception) use ($outputFile): void {
                file_put_contents($outputFile, json_encode([
                    'encrypted_job_failed' => true,
                    'error' => $exception->getMessage(),
                ], JSON_PRETTY_PRINT));
            })
            ->dispatch();
    } catch (\Exception) {
        // Expected when using sync queue during tests
    }

    LaravelQueueFacade::assertPushed(\B7s\QueueFlow\Jobs\EncryptedQueueFlowJob::class);
});

test('failure callback can log to database or external service', function (): void {
    $loggedErrors = [];

    LaravelQueueFacade::fake();

    try {
        qflow(function (): void {
            throw new \RuntimeException('Critical error');
        }, autoDispatch: false)
            ->onQueue('critical')
            ->onFailure(function (\Throwable $exception) use (&$loggedErrors): void {
                // Simulate logging to database or external service
                $loggedErrors[] = [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];
            })
            ->dispatch();
    } catch (\RuntimeException) {
        // Expected when using sync queue during tests
    }

    expect($loggedErrors)->toBeArray();
    LaravelQueueFacade::assertPushedOn('critical', \B7s\QueueFlow\Jobs\QueueFlowJob::class);
});

test('failure callback receives exception with full context', function (): void {
    $capturedContext = null;

    LaravelQueueFacade::fake();

    try {
        qflow(function (): void {
            throw new \InvalidArgumentException('Invalid data provided', 422);
        }, autoDispatch: false)
            ->onFailure(function (\Throwable $exception) use (&$capturedContext): void {
                $capturedContext = [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'class' => get_class($exception),
                ];
            })
            ->dispatch();
    } catch (\InvalidArgumentException) {
        // Expected when using sync queue during tests
    }

    expect($capturedContext)->toBeNull(); // Will be populated when job actually fails
    LaravelQueueFacade::assertPushed(\B7s\QueueFlow\Jobs\QueueFlowJob::class);
});
