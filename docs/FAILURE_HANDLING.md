# Failure Handling

## Overview

QueueFlow provides a fluent way to handle job failures through the `onFailure()` method. This allows you to execute custom logic when a job fails, such as logging errors, sending notifications, or triggering compensating actions.

## Basic Usage

```php
use B7s\QueueFlow\Queue;

$queue = new Queue();
$queue
    ->add(fn () => $this->processOrder($order))
    ->onFailure(function (\Throwable $exception) {
        \Log::error('Order processing failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    })
    ->dispatch();
```

## Using the Helper Function

```php
queue_flow(fn () => $this->sendEmail($user), autoDispatch: false)
    ->onQueue('emails')
    ->onFailure(function (\Throwable $exception) use ($user) {
        // Notify admin about failed email
        \Notification::route('mail', 'admin@example.com')
            ->notify(new EmailFailedNotification($user, $exception));
    })
    ->dispatch();
```

## Common Use Cases

### 1. Logging Errors

```php
queue_flow(fn () => $this->processPayment($payment), autoDispatch: false)
    ->onFailure(function (\Throwable $exception) use ($payment) {
        \Log::channel('payments')->error('Payment processing failed', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    })
    ->dispatch();
```

### 2. Updating Database Records

```php
queue_flow(fn () => $this->syncWithExternalApi($record), autoDispatch: false)
    ->onFailure(function (\Throwable $exception) use ($record) {
        $record->update([
            'sync_status' => 'failed',
            'sync_error' => $exception->getMessage(),
            'last_sync_attempt' => now(),
        ]);
    })
    ->dispatch();
```

### 3. Sending Notifications

```php
queue_flow(fn () => $this->generateReport($report), autoDispatch: false)
    ->onFailure(function (\Throwable $exception) use ($report) {
        $report->user->notify(
            new ReportGenerationFailedNotification($report, $exception)
        );
    })
    ->dispatch();
```

### 4. Triggering Compensating Actions

```php
queue_flow(fn () => $this->reserveInventory($order), autoDispatch: false)
    ->onFailure(function (\Throwable $exception) use ($order) {
        // Release any partial reservations
        $this->releaseInventoryReservations($order);
        
        // Mark order as failed
        $order->update(['status' => 'failed']);
        
        // Notify customer
        $order->customer->notify(new OrderFailedNotification($order));
    })
    ->dispatch();
```

### 5. Retrying with Different Strategy

```php
queue_flow(fn () => $this->processLargeFile($file), autoDispatch: false)
    ->onFailure(function (\Throwable $exception) use ($file) {
        // If it failed due to memory, try with chunked processing
        if ($exception instanceof \OutOfMemoryError) {
            queue_flow(fn () => $this->processFileInChunks($file))
                ->onQueue('low-priority')
                ->dispatch();
        }
    })
    ->dispatch();
```

## Working with Different Job Types

### Unique Jobs

```php
queue_flow(fn () => $this->syncUserData($user), autoDispatch: false)
    ->shouldBeUnique(3600)
    ->onFailure(function (\Throwable $exception) use ($user) {
        \Log::warning('User sync failed', [
            'user_id' => $user->id,
            'error' => $exception->getMessage(),
        ]);
    })
    ->dispatch();
```

### Encrypted Jobs

```php
queue_flow(fn () => $this->processSensitiveData($data), autoDispatch: false)
    ->shouldBeEncrypted()
    ->onFailure(function (\Throwable $exception) {
        // Log without exposing sensitive data
        \Log::error('Sensitive data processing failed', [
            'error_type' => get_class($exception),
            'timestamp' => now(),
        ]);
    })
    ->dispatch();
```

### Rate Limited Jobs

```php
queue_flow(fn () => $this->callExternalApi($endpoint), autoDispatch: false)
    ->rateLimited('api-calls')
    ->onFailure(function (\Throwable $exception) use ($endpoint) {
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            \Log::error('API call failed', [
                'endpoint' => $endpoint,
                'status' => $exception->response->status(),
                'body' => $exception->response->body(),
            ]);
        }
    })
    ->dispatch();
```

## Exception Information

The failure callback receives a `\Throwable` instance with full exception details:

```php
->onFailure(function (\Throwable $exception) {
    // Exception message
    $message = $exception->getMessage();
    
    // Exception code
    $code = $exception->getCode();
    
    // Exception class
    $class = get_class($exception);
    
    // File where exception occurred
    $file = $exception->getFile();
    
    // Line number
    $line = $exception->getLine();
    
    // Stack trace
    $trace = $exception->getTraceAsString();
    
    // Previous exception (if any)
    $previous = $exception->getPrevious();
})
```

## Best Practices

### 1. Keep Failure Callbacks Simple

Failure callbacks should be lightweight and not throw exceptions themselves:

```php
// ✅ Good
->onFailure(function (\Throwable $exception) {
    \Log::error('Job failed', ['error' => $exception->getMessage()]);
})

// ❌ Bad - might throw another exception
->onFailure(function (\Throwable $exception) {
    $this->complexDatabaseOperation(); // Could fail
})
```

### 2. Use Appropriate Logging Levels

```php
->onFailure(function (\Throwable $exception) {
    if ($exception instanceof \RuntimeException) {
        \Log::error('Critical failure', ['error' => $exception->getMessage()]);
    } else {
        \Log::warning('Non-critical failure', ['error' => $exception->getMessage()]);
    }
})
```

### 3. Avoid Sensitive Data in Logs

```php
->onFailure(function (\Throwable $exception) use ($user) {
    \Log::error('User operation failed', [
        'user_id' => $user->id, // ✅ OK
        'email' => $user->email, // ⚠️ Be careful
        'password' => $user->password, // ❌ Never log passwords
        'error' => $exception->getMessage(),
    ]);
})
```

### 4. Consider Idempotency

Ensure failure callbacks can be safely executed multiple times:

```php
->onFailure(function (\Throwable $exception) use ($order) {
    // Use updateOrCreate to be idempotent
    \DB::table('failed_orders')->updateOrCreate(
        ['order_id' => $order->id],
        [
            'error' => $exception->getMessage(),
            'failed_at' => now(),
        ]
    );
})
```

## Testing Failure Callbacks

```php
use Illuminate\Support\Facades\Queue;

test('failure callback is executed when job fails', function () {
    Queue::fake();
    
    $errorLogged = false;
    
    queue_flow(function () {
        throw new \RuntimeException('Test failure');
    }, autoDispatch: false)
        ->onFailure(function () use (&$errorLogged) {
            $errorLogged = true;
        })
        ->dispatch();
    
    Queue::assertPushed(\B7s\QueueFlow\Jobs\QueueFlowJob::class);
    
    // The callback will execute when the job actually runs and fails
});
```

## Limitations

1. **Failure callbacks are serialized**: Keep them simple and avoid using `$this` or complex closures
2. **No return values**: Failure callbacks cannot affect the job's retry behavior
3. **Exceptions in callbacks**: If a failure callback throws an exception, it will be logged but won't stop the job from being marked as failed

## See Also

- [Queue Configuration](INSTALLATION.md#queue-configuration)
- [Usage Examples](USAGE_EXAMPLES.md)
- [Testing](TESTING.md)
