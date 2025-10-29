<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Tests;

use B7s\QueueFlow\QueueFlowServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            QueueFlowServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('queue.default', 'sync');
        config()->set('app.key', 'base64:O4S8y7zQv5x0pQb4xWlH0xv1cY4FDrT9T5PteF1mROw=');
    }
}
