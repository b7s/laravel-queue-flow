# Installation Guide

This guide will walk you through installing and configuring QueueFlow Laravel Queue in your Laravel application.

## Requirements

- PHP 8.3 or higher
- Laravel 11.0 or higher
- Composer

## Installation Steps

### 1. Install via Composer

```bash
composer require b7s/laravel-queue-flow
```

### 2. Service Provider Registration

The service provider will be automatically registered via Laravel's package auto-discovery. No manual registration needed.

### 3. Publish Configuration (Optional)

If you want to customize the default configuration:

```bash
php artisan vendor:publish --tag=parallite-config
```

This will create a `config/queue-flow.php` file in your Laravel application.

### 4. Configure Queue Connection

Make sure your Laravel application has a queue connection configured in `config/queue.php`.

For development, you can use the `sync` driver:

```php
// .env
QUEUE_CONNECTION=sync
```

For production, use a proper queue driver like Redis, Database, or SQS:

```php
// .env
QUEUE_CONNECTION=redis
```

### 5. Run Queue Worker (Production)

In production, you'll need to run a queue worker:

```bash
php artisan queue:work
```

Or use Supervisor to keep the worker running:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=your-user
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
```

## Configuration

### Default Configuration

The default configuration file looks like this:

```php
<?php

return [
    'connection' => env('PARALLITE_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    'queue' => env('PARALLITE_QUEUE_NAME', 'default'),
    'unique_for' => env('PARALLITE_UNIQUE_FOR', 3600),
    'rate_limiters' => [
        'default' => [
            'limit' => 60,
            'per_minute' => 1,
        ],
    ],
];
```

### Environment Variables

You can customize the configuration using environment variables:

```env
# Queue connection (defaults to QUEUE_CONNECTION)
PARALLITE_QUEUE_CONNECTION=redis

# Default queue name
PARALLITE_QUEUE_NAME=default

# Default unique job duration (in seconds)
PARALLITE_UNIQUE_FOR=3600
```

### Rate Limiters

Configure rate limiters in `config/queue-flow.php`:

```php
'rate_limiters' => [
    'default' => [
        'limit' => 60,
        'per_minute' => 1,
    ],
    'api-calls' => [
        'limit' => 100,
        'per_minute' => 1,
    ],
    'email-sending' => [
        'limit' => 50,
        'per_minute' => 1,
    ],
],
```

## Verification

To verify the installation, create a simple test:

```php
<?php

namespace App\Http\Controllers;

use B7s\QueueFlow\Queue;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    private Queue $queue;

    public function __construct()
    {
        $this->queue = new Queue();
    }

    public function test()
    {
        $this->queue
            ->add(fn () => Log::info('QueueFlow Queue is working!'))
            ->dispatch();

        return response()->json(['message' => 'Job dispatched']);
    }
}
```

Visit the route and check your logs. You should see "QueueFlow Queue is working!" in your log file.

## Troubleshooting

### Jobs Not Processing

1. **Check queue connection**: Ensure your queue connection is properly configured
2. **Run queue worker**: Make sure `php artisan queue:work` is running
3. **Check failed jobs**: Run `php artisan queue:failed` to see failed jobs
4. **Check logs**: Look in `storage/logs/laravel.log` for errors

### Closure Serialization Issues

If you encounter serialization issues:

1. Keep closures simple
2. Avoid using `$this` in closures (use `use` keyword instead)
3. Use `withoutRelations()` for Eloquent models
4. Consider using `shouldBeEncrypted()` for sensitive data

### Performance Issues

1. Use appropriate queue drivers (Redis, SQS, etc.)
2. Run multiple queue workers
3. Use different queues for different priorities
4. Apply rate limiting for external API calls

## Next Steps

- Read the [Usage Examples](USAGE_EXAMPLES.md) for practical examples
- Check the [Architecture Documentation](ARCHITECTURE.md) to understand how it works
- Explore the [README](../README.md) for API reference

## Support

If you encounter any issues:

1. Check the [GitHub Issues](https://github.com/b7s/laravel-queue-flow/issues)
2. Read the documentation
3. Open a new issue with details about your problem
