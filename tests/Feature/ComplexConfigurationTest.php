<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('configures complex job with manual dispatch', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_cplx_feature_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $queue = qflow(static function () use ($outputFile): void {
        $result = [
            'status' => 'completed',
            'priority' => 'high',
            'processed_at' => now()->toIso8601String(),
        ];

        file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT));
    }, autoDispatch: false);

    $queue
        ->onQueue('high-priority')
        ->shouldBeUnique(3600)
        ->withoutRelations()
        ->dispatch();

    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['status'])->toBe('completed');
    expect($content['priority'])->toBe('high');
});
