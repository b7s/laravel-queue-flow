<?php

declare(strict_types=1);

use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('processes multiple jobs sequentially', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_seq_feature_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    queue_flow([
        static function () use ($outputFile): void {
            file_put_contents($outputFile, json_encode(['jobs' => ['job-1']], JSON_PRETTY_PRINT));
        },
        static function () use ($outputFile): void {
            $current = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
            $current['jobs'][] = 'job-2';
            file_put_contents($outputFile, json_encode($current, JSON_PRETTY_PRINT));
        },
        static function () use ($outputFile): void {
            $current = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
            $current['jobs'][] = 'job-3';
            $current['completed_at'] = now()->toIso8601String();
            file_put_contents($outputFile, json_encode($current, JSON_PRETTY_PRINT));
        },
    ]);

    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['jobs'])->toBe(['job-1', 'job-2', 'job-3']);
    expect($content)->toHaveKey('completed_at');
});
