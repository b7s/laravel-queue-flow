
# Understanding the `dispatch()` return value

When you call `dispatch()`, it always returns an instance of Laravel's [`PendingDispatch`](https://laravel.com/docs/11.x/queues#dispatching-jobs) wrapper. All fluent configuration performed on the `Queue` instance (for example `onQueue()`, `delay()`, `shouldBeUnique()`) is applied **before** the job is handed off to Laravel. If you pass an array of closures to `add()`, `dispatch()` will return an Collection of `PendingDispatch` instances, which you can iterate through and then use to apply different configurations to each job.

### The returned `PendingDispatch` then lets you:

1. Add contextual middleware dynamically, such as rate limiters:

   ```php
   // Dispatch one job
   $pendingDispatch = $this->myQueue
       ->add(fn () => $this->callExternalApi())
       ->dispatch();

   $pendingDispatch->through([
       new \Illuminate\Queue\Middleware\RateLimited('api-calls'),
   ]);

   // Dispatch multiple jobs
   $pendingDispatch = $this->myQueue
       ->add([
           fn () => $this->callExternalApi(),
           fn () => $this->callExternalApi(),
       ])
       ->dispatch()
       // Apply middleware to each dispatch
       ->each(fn ($dispatch) => $dispatch->through([new \Illuminate\Queue\Middleware\RateLimited('api-calls')]));
   ```

2. Switch execution mode depending on runtime needs:

   ```php
   $this->myQueue
       ->add(fn () => $this->generateReport())
       ->dispatch()
       ->dispatchSync(); // run immediately inside the current process
   ```

3. Defer execution until after the HTTP response has been sent:

   ```php
   $this->myQueue
       ->add(fn () => $this->sendLargeExport())
       ->dispatch()
       ->afterResponse();
   ```

The job is enqueued as soon as the `PendingDispatch` is destroyed (for example, when it falls out of scope). In production code this happens automatically; you do not need additional cleanup.

> **Note:** If you don't want to return a Collection of `PendingDispatch` instances, you can set the `dispatch_return_of_multiple_jobs_as_collection` configuration option to `false`, then `dispatch()` will return an array of `PendingDispatch` instances.
