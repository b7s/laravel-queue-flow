
# Understanding the `dispatch()` return value

Calling `dispatch()` always returns an instance of Laravel's [`PendingDispatch`](https://laravel.com/docs/11.x/queues#dispatching-jobs) wrapper. All fluent configuration performed on the `Queue` instance (for example `onQueue()`, `delay()`, `shouldBeUnique()`) is applied **before** the job is handed off to Laravel. The returned `PendingDispatch` then lets you:

1. Add contextual middleware dynamically, such as rate limiters:

   ```php
   $pendingDispatch = $this->myQueue
       ->add(fn () => $this->callExternalApi())
       ->dispatch();

   $pendingDispatch->through([
       new \Illuminate\Queue\Middleware\RateLimited('api-calls'),
   ]);
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
