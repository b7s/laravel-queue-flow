# QueueFlow Queue Usage Examples

This document provides practical examples of using the QueueFlow Queue package in various scenarios.

## Table of Contents

- [Basic Examples](#basic-examples)
- [Email Notifications](#email-notifications)
- [Data Processing](#data-processing)
- [API Integrations](#api-integrations)
- [Report Generation](#report-generation)
- [Advanced Patterns](#advanced-patterns)

## Basic Examples

### Simple Task Queue

```php
<?php

namespace App\Http\Controllers;

class TaskController extends Controller
{
    public function processTask(): void
    {
        // Dispatch automatically
        qflow(fn () => $this->heavyComputation());
    }

    private function heavyComputation(): void
    {
        // Your heavy computation logic
    }
}
```

### Delayed Task

```php
public function scheduleTask(): void
{
    // Dispatch manually after configuration
    qflow((fn () => $this->sendReminder(), false))
        ->delay(now()->addHours(24))
        ->dispatch();
}
```

## Email Notifications

### Send Welcome Email

```php
<?php

namespace App\Services;

use App\Models\User;
use B7s\QueueFlow\Queue;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class UserService
{
    private Queue $emailQueue;

    public function __construct()
    {
        $this->emailQueue = new Queue();
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->emailQueue
            ->add(fn () => Mail::to($user->email)->send(new WelcomeEmail($user)))
            ->onQueue('emails')
            ->delay(now()->addMinutes(5))
            ->shouldBeUnique(3600) // Only once per hour
            ->dispatch();
    }
}
```

### Bulk Email Campaign

```php
public function sendCampaign(array $users): void
{
    foreach ($users as $user) {
        $this->emailQueue
            ->add(fn () => $this->sendCampaignEmail($user))
            ->onQueue('campaigns')
            ->rateLimited('email-campaign')
            ->dispatch();
    }
}
```

## Data Processing

### Process Large Dataset

```php
<?php

namespace App\Services;

use B7s\QueueFlow\Queue;
use App\Models\Order;

class OrderProcessingService
{
    private Queue $processingQueue;

    public function __construct()
    {
        $this->processingQueue = new Queue();
    }

    public function processOrders(array $orderIds): void
    {
        foreach ($orderIds as $orderId) {
            $this->processingQueue
                ->add(fn () => $this->processOrder($orderId))
                ->onQueue('order-processing')
                ->withoutRelations()
                ->shouldBeUniqueUntilProcessing()
                ->dispatch();
        }
    }

    private function processOrder(int $orderId): void
    {
        $order = Order::find($orderId);
        // Process order logic
    }
}
```

### Import CSV Data

```php
public function importCsv(string $filePath): void
{
    $this->processingQueue
        ->add(fn () => $this->processCsvFile($filePath))
        ->onQueue('imports')
        ->delay(now()->addSeconds(30))
        ->shouldBeUnique(7200) // Prevent duplicate imports
        ->dispatch();
}

private function processCsvFile(string $filePath): void
{
    // CSV processing logic
}
```

## API Integrations

### Sync with External API

```php
<?php

namespace App\Services;

use B7s\QueueFlow\Queue;
use Illuminate\Support\Facades\Http;

class ApiSyncService
{
    private Queue $apiQueue;

    public function __construct()
    {
        $this->apiQueue = new Queue();
    }

    public function syncUserData(int $userId): void
    {
        $this->apiQueue
            ->add(fn () => $this->callExternalApi($userId))
            ->onQueue('api-sync')
            ->rateLimited('external-api')
            ->shouldBeEncrypted() // Sensitive data
            ->dispatch();
    }

    private function callExternalApi(int $userId): void
    {
        $response = Http::post('https://api.example.com/sync', [
            'user_id' => $userId,
        ]);
        
        // Handle response
    }
}
```

### Webhook Processing

```php
public function processWebhook(array $payload): void
{
    $this->apiQueue
        ->add(fn () => $this->handleWebhookPayload($payload))
        ->onQueue('webhooks')
        ->shouldBeUniqueUntilProcessing()
        ->dispatch();
}
```

## Report Generation

### Generate Monthly Report

```php
<?php

namespace App\Services;

use B7s\QueueFlow\Queue;
use App\Models\Report;

class ReportService
{
    private Queue $reportQueue;

    public function __construct()
    {
        $this->reportQueue = new Queue();
    }

    public function generateMonthlyReport(int $month, int $year): void
    {
        $this->reportQueue
            ->add(fn () => $this->createReport($month, $year))
            ->onQueue('reports')
            ->delay(now()->addMinutes(5))
            ->shouldBeUnique(86400) // Once per day
            ->dispatch();
    }

    private function createReport(int $month, int $year): void
    {
        // Generate report logic
        $report = Report::create([
            'month' => $month,
            'year' => $year,
            'data' => $this->collectReportData($month, $year),
        ]);
    }

    private function collectReportData(int $month, int $year): array
    {
        // Collect data logic
        return [];
    }
}
```

## Advanced Patterns

### Dependency Injection with Queue

```php
<?php

namespace App\Services;

use B7s\QueueFlow\Queue;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

class UserNotificationService
{
    public function __construct(
        private Queue $queue,
        private UserRepository $userRepository
    ) {
    }

    public function notifyUsers(array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->queue
                ->add(function () use ($userId) {
                    $user = $this->userRepository->find($userId);
                    Log::info("Notifying user: {$user->email}");
                    // Send notification
                })
                ->onQueue('notifications')
                ->dispatch();
        }
    }
}
```

### Conditional Queue Configuration

```php
public function processWithPriority(string $priority, callable $task): void
{
    $queue = $this->queue->add($task);

    match ($priority) {
        'high' => $queue->onQueue('high-priority')->delay(0),
        'medium' => $queue->onQueue('medium-priority')->delay(now()->addMinutes(5)),
        'low' => $queue->onQueue('low-priority')->delay(now()->addMinutes(15)),
    };

    $queue->dispatch();
}
```

### Batch Processing with Rate Limiting

```php
public function processBatch(array $items, string $limiterName): void
{
    foreach ($items as $item) {
        $this->queue
            ->add(fn () => $this->processItem($item))
            ->onQueue('batch-processing')
            ->rateLimited($limiterName)
            ->withoutRelations()
            ->dispatch();
    }
}
```

### Auto-dispatch Pattern

```php
public function quickTask(): void
{
    // No need to call dispatch() - will auto-dispatch on destruction
    $this->queue->add(fn () => $this->doQuickWork());
}
```

### Error Handling in Closures

```php
public function processWithErrorHandling(int $itemId): void
{
    $this->queue
        ->add(function () use ($itemId) {
            try {
                $this->processItem($itemId);
            } catch (\Exception $e) {
                Log::error("Failed to process item {$itemId}: {$e->getMessage()}");
                throw $e; // Re-throw to trigger Laravel's failed job handling
            }
        })
        ->onQueue('processing')
        ->dispatch();
}
```

### Multiple Queues in One Class

```php
<?php

namespace App\Services;

use B7s\QueueFlow\Queue;

class MultiQueueService
{
    private Queue $emailQueue;
    private Queue $processingQueue;
    private Queue $reportQueue;

    public function __construct()
    {
        $this->emailQueue = new Queue();
        $this->processingQueue = new Queue();
        $this->reportQueue = new Queue();
    }

    public function handleUserAction(int $userId): void
    {
        // Send email
        $this->emailQueue
            ->add(fn () => $this->sendEmail($userId))
            ->onQueue('emails')
            ->dispatch();

        // Process data
        $this->processingQueue
            ->add(fn () => $this->processData($userId))
            ->onQueue('processing')
            ->delay(now()->addMinutes(5))
            ->dispatch();

        // Generate report
        $this->reportQueue
            ->add(fn () => $this->generateReport($userId))
            ->onQueue('reports')
            ->delay(now()->addHours(1))
            ->dispatch();
    }
}
```

## Best Practices

1. **Use specific queue names** for different types of jobs
2. **Apply rate limiting** for external API calls
3. **Use encryption** for sensitive data
4. **Use unique jobs** to prevent duplicates
5. **Use withoutRelations()** for Eloquent models to reduce payload size
6. **Add delays** for non-urgent tasks to balance load
7. **Handle errors** within closures for better debugging
8. **Keep closures small** - extract complex logic to methods
