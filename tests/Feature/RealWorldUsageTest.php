<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('processes user registration with queue', function () {
    // Use temp dir to avoid problems to delete the file - if any error occurs, the file will be deleted on shutdown
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_queue_feature_test_') . '.json';
    register_shutdown_function(fn() => @unlink($outputFile));

    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'registered_at' => now()->toIso8601String(),
    ];

    $queue = new Queue();
    $queue
        ->add(function () use ($userData, $outputFile): void {
            // Simulate sending welcome email
            $result = [
                'user' => $userData,
                'email_sent' => true,
                'timestamp' => now()->toIso8601String(),
            ];

            file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT));
        })
        ->onQueue('emails')
        ->delay(1) // 1 second delay
        ->dispatch();

    // Verify file was created and contains correct data
    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode(file_get_contents($outputFile), true);

    expect($content)->toHaveKey('user');
    expect($content)->toHaveKey('email_sent');
    expect($content)->toHaveKey('timestamp');
    expect($content['user']['name'])->toBe('John Doe');
    expect($content['user']['email'])->toBe('john@example.com');
    expect($content['email_sent'])->toBeTrue();
});
