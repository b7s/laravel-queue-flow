<?php

declare(strict_types=1);

namespace B7s\QueueFlow;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class QueueFlowServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/queue-flow.php',
            'queue-flow'
        );

        $this->app->bind(Queue::class, static fn () => new Queue());

        require_once __DIR__ . '/Support/helpers.php';
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/queue-flow.php' => config_path('queue-flow.php'),
            ], 'queue-flow-config');
        }

        $this->registerRateLimiters();
    }

    /**
     * Register rate limiters from configuration
     */
    protected function registerRateLimiters(): void
    {
        $rateLimiters = config('queue-flow.rate_limiters', []);

        foreach ($rateLimiters as $name => $config) {
            RateLimiter::for($name, function () use ($config) {
                $limit = (int) ($config['limit'] ?? 60);
                $perMinute = (int) ($config['per_minute'] ?? 1);

                return Limit::perMinutes($perMinute, max($limit, 1));
            });
        }
    }
}
