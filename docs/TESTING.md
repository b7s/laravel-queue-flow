# QueueFlow Queue Testing

## Overview

🧪 Let's teste! The package includes a comprehensive test suite that covers all aspects of QueueFlow Queue's functionality, including the behavior of the `qflow()` helper with different `autoDispatch` configurations.

## Test Structure

### Unit Tests

#### `tests/Unit/ParalliteQueueTest.php`
Basic tests for the `Queue` class:
- Instantiation
- Adding callbacks to the queue
- Behavior of the `qflow()` helper

#### `tests/Unit/HelperChainConfigurationTest.php`
Specific tests for the helper's chaining behavior:

1. **Chaining with `autoDispatch: false`**
   - Allows chained configurations before `dispatch()`
   - Doesn't execute the job until `dispatch()` is called
   ```php
   qflow(fn() => doSomething(), autoDispatch: false)
       ->delay(60)
       ->onQueue('high')
       ->dispatch();
   ```

2. **Immediate Execution with `autoDispatch: true`**
   - Executes the job immediately without allowing chaining
   ```php
   qflow(fn() => doSomething(), autoDispatch: true);
   ```

3. **Default Behavior**
   - By default, `autoDispatch` is `true`
   ```php
   // Executes immediately
   qflow(fn() => doSomething());
   ```

4. **Queue Configuration with Fake**
   - Tests queue configurations using `Queue::fake()`
   - Verifies jobs are sent with correct configurations

### Feature Tests

#### `tests/Feature/RealWorldUsageTest.php`
Example of package usage in a real-world user registration scenario.

#### `tests/Feature/SequenceJobsTest.php`
Tests for sequential job processing.

#### `tests/Feature/DatabaseFlowTest.php`
Complete database flow tests, including:
- Temporary table creation
- CRUD operations within transactions
- Automatic rollback after tests

## Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
composer test -- --testsuite=Unit

# Run only feature tests
composer test -- --testsuite=Feature

# Run a specific test file
composer test -- tests/Unit/HelperChainConfigurationTest.php
```

## Testing Conventions

1. **Temporary Files**
   - Use `sys_get_temp_dir()` for temporary files
   - Register cleanup with `register_shutdown_function()`
   ```php
   $file = tempnam(sys_get_temp_dir(), 'prefix_') . '.txt';
   register_shutdown_function(fn() => @unlink($file));
   ```

2. **Test Naming**
   - Descriptive and in English
   - Format: `verb_subject_expected_behavior`
   - Example: `test_helper_with_auto_dispatched_false_allows_chaining`

3. **Assertions**
   - Use Pest helpers for better readability
   - Include descriptive messages in assertions
   ```php
   expect($result)->toBeTrue('Expected the operation to succeed');
   ```

## Best Practices

1. **Isolation**
   - Each test should be independent
   - Use transactions for database tests
   - Clean up resources after each test

2. **Performance**
   - Use SQLite in-memory when possible
   - Avoid slow calls in test loops
   - Use `$this->withoutExceptionHandling()` only when necessary

3. **Readability**
   - Use descriptive names for variables and tests
   - Comment non-obvious code
   - Group related tests with `describe()` or `group()`

## Integration Testing

To test integration with real services, create a separate testing environment with:

1. Real database configuration
2. Queue instead of `sync`
3. Mocked external services

## Troubleshooting

If a test fails:

1. Run the test in isolation
2. Check detailed error messages
3. Use `dump()` or `dd()` for debugging
4. Check for side effects from other tests

## Code Coverage

To generate a coverage report:

```bash
composer test-coverage
```

This will generate a report in `coverage/index.html` that can be viewed in a browser.
