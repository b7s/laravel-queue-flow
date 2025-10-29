<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;
use Illuminate\Support\Facades\Queue as LaravelQueueFacade;

uses(TestCase::class);

test('helper with autoDispatch false allows method chaining before dispatch', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_chain_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $queue = queue_flow(static function () use ($outputFile): void {
        file_put_contents($outputFile, json_encode([
            'executed' => true,
            'timestamp' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }, autoDispatch: false);

    expect($queue)->toBeInstanceOf(Queue::class);
    expect(file_exists($outputFile))->toBeFalse('Job should not execute before dispatch');

    $queue
        ->delay(1)
        ->onQueue('test-queue')
        ->onConnection('sync')
        ->withoutRelations()
        ->shouldBeUnique(3600)
        ->shouldBeEncrypted();

    expect(file_exists($outputFile))->toBeFalse('Job should still not execute after chaining');

    $queue->dispatch();

    expect(file_exists($outputFile))->toBeTrue('Job should execute after explicit dispatch');

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['executed'])->toBeTrue();
});

test('helper with autoDispatch true executes immediately without chaining', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_chain_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $queue = queue_flow(static function () use ($outputFile): void {
        file_put_contents($outputFile, json_encode([
            'executed' => true,
            'auto_dispatched' => true,
        ], JSON_PRETTY_PRINT));
    }, autoDispatch: true);

    expect($queue)->toBeInstanceOf(Queue::class);
    expect(file_exists($outputFile))->toBeTrue('Job should execute immediately with autoDispatch true');

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['executed'])->toBeTrue();
    expect($content['auto_dispatched'])->toBeTrue();
});

test('helper default behavior auto-dispatches immediately', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_chain_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    queue_flow(static function () use ($outputFile): void {
        file_put_contents($outputFile, json_encode([
            'executed' => true,
            'default_behavior' => true,
        ], JSON_PRETTY_PRINT));
    });

    expect(file_exists($outputFile))->toBeTrue('Job should execute immediately by default');

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['executed'])->toBeTrue();
    expect($content['default_behavior'])->toBeTrue();
});

test('helper with autoDispatch false and Queue fake verifies job configuration', function (): void {
    LaravelQueueFacade::fake();

    $queue = queue_flow(static fn (): string => 'test-job', autoDispatch: false)
        ->delay(60)
        ->onQueue('priority-queue')
        ->onConnection('redis')
        ->withoutRelations()
        ->shouldBeUnique(7200)
        ->shouldBeEncrypted();

    LaravelQueueFacade::assertNothingPushed();

    $queue->dispatch();

    LaravelQueueFacade::assertPushedOn('priority-queue', \B7s\QueueFlow\Jobs\UniqueQueueFlowJob::class);
});

test('helper returns Queue instance for further manipulation', function (): void {
    $queue = queue_flow(static fn (): string => 'test', autoDispatch: false);

    expect($queue)->toBeInstanceOf(Queue::class);
    expect(method_exists($queue, 'delay'))->toBeTrue();
    expect(method_exists($queue, 'onQueue'))->toBeTrue();
    expect(method_exists($queue, 'onConnection'))->toBeTrue();
    expect(method_exists($queue, 'withoutRelations'))->toBeTrue();
    expect(method_exists($queue, 'shouldBeUnique'))->toBeTrue();
    expect(method_exists($queue, 'shouldBeEncrypted'))->toBeTrue();
    expect(method_exists($queue, 'dispatch'))->toBeTrue();
});

test('helper with complex chaining configuration executes correctly', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_chain_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $data = [
        'user_id' => 123,
        'action' => 'export',
        'priority' => 'high',
    ];

    queue_flow(static function () use ($outputFile, $data): void {
        file_put_contents($outputFile, json_encode([
            'data' => $data,
            'processed' => true,
            'timestamp' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }, autoDispatch: false)
        ->delay(5)
        ->onQueue('exports')
        ->onConnection('sync')
        ->withoutRelations()
        ->shouldBeUnique(1800)
        ->shouldBeEncrypted()
        ->dispatch();

    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['processed'])->toBeTrue();
    expect($content['data']['user_id'])->toBe(123);
    expect($content['data']['action'])->toBe('export');
    expect($content['data']['priority'])->toBe('high');
});
