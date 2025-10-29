<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Services\QueueConfigurationService;
use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('QueueConfigurationService loads defaults from config', function (): void {
    config([
        'queue-flow.queue' => 'custom-queue',
        'queue-flow.connection' => 'redis',
        'queue-flow.unique_for' => 7200,
    ]);

    $service = new QueueConfigurationService();

    expect($service->getQueue())->toBe('custom-queue')
        ->and($service->getConnection())->toBe('redis')
        ->and($service->getUniqueFor())->toBe(7200);
});

test('QueueConfigurationService uses fallback defaults when config is null', function (): void {
    config([
        'queue-flow.queue' => null,
        'queue-flow.connection' => null,
        'queue-flow.unique_for' => null,
    ]);

    $service = new QueueConfigurationService();

    expect($service->getQueue())->toBeNull()
        ->and($service->getConnection())->toBeNull()
        ->and($service->getUniqueFor())->toBe(3600);
});

test('reset method restores config defaults', function (): void {
    config([
        'queue-flow.queue' => 'default-queue',
        'queue-flow.connection' => 'sync',
        'queue-flow.unique_for' => 1800,
    ]);

    $service = new QueueConfigurationService();
    
    $service->setQueue('custom-queue');
    $service->setConnection('redis');
    $service->setUniqueFor(9999);

    expect($service->getQueue())->toBe('custom-queue')
        ->and($service->getConnection())->toBe('redis')
        ->and($service->getUniqueFor())->toBe(9999);

    $service->reset();

    expect($service->getQueue())->toBe('default-queue')
        ->and($service->getConnection())->toBe('sync')
        ->and($service->getUniqueFor())->toBe(1800);
});

test('shouldBeUnique uses config default when no parameter provided', function (): void {
    config(['queue-flow.unique_for' => 5400]);

    $queue = new Queue();
    $queue->add(fn () => true)->shouldBeUnique();

    $reflection = new ReflectionClass($queue);
    $configServiceProperty = $reflection->getProperty('configService');
    $configServiceProperty->setAccessible(true);
    $configService = $configServiceProperty->getValue($queue);

    expect($configService->getUniqueFor())->toBe(5400);
});

test('shouldBeUnique accepts custom value overriding config', function (): void {
    config(['queue-flow.unique_for' => 5400]);

    $queue = new Queue();
    $queue->add(fn () => true)->shouldBeUnique(10800);

    $reflection = new ReflectionClass($queue);
    $configServiceProperty = $reflection->getProperty('configService');
    $configServiceProperty->setAccessible(true);
    $configService = $configServiceProperty->getValue($queue);

    expect($configService->getUniqueFor())->toBe(10800);
});

test('Queue uses config defaults for queue and connection', function (): void {
    config([
        'queue-flow.queue' => 'high-priority',
        'queue-flow.connection' => 'database',
    ]);

    $queue = new Queue();

    $reflection = new ReflectionClass($queue);
    $configServiceProperty = $reflection->getProperty('configService');
    $configServiceProperty->setAccessible(true);
    $configService = $configServiceProperty->getValue($queue);

    expect($configService->getQueue())->toBe('high-priority')
        ->and($configService->getConnection())->toBe('database');
});

test('environment variables override config defaults', function (): void {
    putenv('PARALLITE_QUEUE_CONNECTION=sqs');
    putenv('PARALLITE_QUEUE_NAME=emails');
    putenv('PARALLITE_UNIQUE_FOR=14400');

    config([
        'queue-flow.connection' => env('PARALLITE_QUEUE_CONNECTION', 'sync'),
        'queue-flow.queue' => env('PARALLITE_QUEUE_NAME', 'default'),
        'queue-flow.unique_for' => env('PARALLITE_UNIQUE_FOR', 3600),
    ]);

    $service = new QueueConfigurationService();

    expect($service->getConnection())->toBe('sqs')
        ->and($service->getQueue())->toBe('emails')
        ->and($service->getUniqueFor())->toBe(14400);

    putenv('PARALLITE_QUEUE_CONNECTION');
    putenv('PARALLITE_QUEUE_NAME');
    putenv('PARALLITE_UNIQUE_FOR');
});
