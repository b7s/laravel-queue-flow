<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Bus;

uses(TestCase::class);

test('can instantiate Queue', function () {
    $queue = new Queue();

    expect($queue)->toBeInstanceOf(Queue::class);
});

test('can add closure to queue', function () {
    $queue = new Queue();

    $result = $queue->add(function (): bool {
        return true;
    });

    expect($result)->toBeInstanceOf(Queue::class);
});

test('can chain configuration methods', function () {
    $queue = new Queue();
    
    $result = $queue
        ->add(fn () => true)
        ->delay(60)
        ->onQueue('test')
        ->onConnection('sync')
        ->withoutRelations()
        ->shouldBeUnique()
        ->shouldBeEncrypted();

    expect($result)->toBeInstanceOf(Queue::class);
});

test('can resolve queue instance from container', function () {
    $queue = app(Queue::class);

    expect($queue)->toBeInstanceOf(Queue::class);
});

test('make helper resolves via container', function () {
    $queue = Queue::make();

    expect($queue)->toBeInstanceOf(Queue::class);
});

test('container injects queue into dependencies', function () {
    $service = app()->make(ParalliteQueueConsumer::class);

    expect($service->queue)->toBeInstanceOf(Queue::class);
});

test('helper auto-dispatches by default', function () {
    $testFile = tempnam(sys_get_temp_dir(), 'parallite_queue_unit_test_') . '.txt';
    register_shutdown_function(fn() => @unlink($testFile));

    qflow(function () use ($testFile): void {
        file_put_contents($testFile, 'executed');
    });

    expect(file_exists($testFile))->toBeTrue();
    expect(file_get_contents($testFile))->toBe('executed');
});

test('helper can disable auto dispatch', function () {
    $testFile = tempnam(sys_get_temp_dir(), 'parallite_queue_unit_test_') . '.txt';
    register_shutdown_function(fn() => @unlink($testFile));

    $queue = qflow(function () use ($testFile): void {
        file_put_contents($testFile, 'executed');
    }, autoDispatch: false);

    expect(file_exists($testFile))->toBeFalse();

    $queue->dispatch();

    expect(file_exists($testFile))->toBeTrue();
    expect(file_get_contents($testFile))->toBe('executed');
});

test('add accepts multiple closures and reuses configuration', function (): void {
    Bus::fake();

    $queue = (new Queue())
        ->autoDispatch(false)
        ->onQueue('batch-jobs')
        ->add([
            static fn (): string => 'first',
            static fn (): string => 'second',
            static fn (): string => 'third'
        ]);

    $dispatches = $queue->dispatch();

    // Verify return type is correct
    expect($dispatches)
        ->toBeCollection()
        ->toHaveCount(3)
        ->and($dispatches[0])->toBeInstanceOf(PendingDispatch::class)
        ->and($dispatches[1])->toBeInstanceOf(PendingDispatch::class)
        ->and($dispatches[2])->toBeInstanceOf(PendingDispatch::class);

    // Force destruction of PendingDispatch objects to trigger actual dispatch
    unset($dispatches);

    // Verify jobs were dispatched to the queue
    Bus::assertDispatched(\B7s\QueueFlow\Jobs\QueueFlowJob::class, 3);
    Bus::assertDispatched(\B7s\QueueFlow\Jobs\QueueFlowJob::class, function ($job) {
        return $job->queue === 'batch-jobs';
    });
});

test('add throws when array does not contain closures', function (): void {
    $queue = new Queue();

    $queue->add(['invalid-callback']);
})->throws(InvalidArgumentException::class, 'Queue::add expects at least one Closure instance.');

test('throws exception when dispatching without callback', function () {
    $queue = new Queue();
    
    $queue->dispatch();
})->throws(RuntimeException::class, 'No callback has been added to the queue');

class ParalliteQueueConsumer
{
    public function __construct(
        public Queue $queue,
    ) {
    }
}
