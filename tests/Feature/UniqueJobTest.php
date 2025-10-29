<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('handles unique job processing', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_unique_feature_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $jobId = 'report-123';

    (new Queue())
        ->add(static function () use ($jobId, $outputFile): void {
            $data = [
                'job_id' => $jobId,
                'processed_at' => now()->toIso8601String(),
                'attempt' => 1,
            ];

            file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT));
        })
        ->shouldBeUnique(60)
        ->dispatch();

    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['job_id'])->toBe($jobId);
    expect($content['attempt'])->toBe(1);
});
