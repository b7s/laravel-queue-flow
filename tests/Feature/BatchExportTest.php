<?php

declare(strict_types=1);

use B7s\QueueFlow\Tests\TestCase;

uses(TestCase::class);

test('exports catalog snapshot using helper', function (): void {
    $outputFile = tempnam(sys_get_temp_dir(), 'parallite_batch_feature_test_') . '.json';
    register_shutdown_function(static fn (): bool => @unlink($outputFile));

    $records = [
        ['id' => 1, 'name' => 'Product A', 'price' => 99.99],
        ['id' => 2, 'name' => 'Product B', 'price' => 149.99],
        ['id' => 3, 'name' => 'Product C', 'price' => 199.99],
    ];

    qflow(static function () use ($records, $outputFile): void {
        $export = [
            'total_records' => count($records),
            'records' => $records,
            'exported_at' => now()->toIso8601String(),
            'format' => 'json',
        ];

        file_put_contents($outputFile, json_encode($export, JSON_PRETTY_PRINT));
    });

    expect(file_exists($outputFile))->toBeTrue();

    $content = json_decode((string) file_get_contents($outputFile), true);

    expect($content['total_records'])->toBe(3);
    expect($content['records'])->toHaveCount(3);
    expect($content['records'][0]['name'])->toBe('Product A');
    expect($content['format'])->toBe('json');
});
