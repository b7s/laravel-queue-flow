<?php

declare(strict_types=1);

use B7s\QueueFlow\Queue;
use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('processes encrypted sensitive data', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_enc_feature_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $sensitiveData = [
        'ssn' => '123-45-6789',
        'credit_card' => '4111-1111-1111-1111',
        'password' => 'super-secret',
    ];

    (new Queue())
        ->add(static function () use ($sensitiveData, $outputFile): void {
            $processed = [
                'data_processed' => true,
                'fields_count' => count($sensitiveData),
                'timestamp' => now()->toIso8601String(),
            ];

            file_put_contents($outputFile, json_encode($processed, JSON_PRETTY_PRINT));
        })
        ->shouldBeEncrypted()
        ->dispatch();

    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode((string) file_get_contents($outputFile), true, flags: JSON_THROW_ON_ERROR);
    expect($content['data_processed'])->toBeTrue();
    expect($content['fields_count'])->toBe(3);
});
