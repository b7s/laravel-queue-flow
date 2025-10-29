# QueueFlow Queue Architecture

This document explains the architecture and flow of the QueueFlow Laravel Queue package.

## Overview

QueueFlow Queue is a fluent wrapper around Laravel's queue system that simplifies job dispatching by eliminating the need to create separate Job classes for each task.

## Components

### 1. Queue (Main Interface)

**Location**: `src/Queue.php`

**Purpose**: Main entry point for users. Provides a fluent interface for configuring and dispatching jobs.

**Responsibilities**:
- Accept closures via `add()` method
- Provide configuration methods (delay, queue, connection, etc.)
- Coordinate with services to dispatch jobs
- Auto-dispatch on object destruction

**Key Methods**:
- `add(Closure $callback)`: Sets the callback to be executed
- `delay()`: Sets job delay
- `onQueue()`: Sets queue name
- `onConnection()`: Sets connection name
- `withoutRelations()`: Prevents relation serialization
- `shouldBeUnique()`: Makes job unique
- `shouldBeUniqueUntilProcessing()`: Makes job unique until processing
- `shouldBeEncrypted()`: Encrypts job payload
- `rateLimited()`: Applies rate limiting
- `dispatch()`: Dispatches the job

### 2. QueueConfigurationService

**Location**: `src/Services/QueueConfigurationService.php`

**Purpose**: Manages the configuration state for queue jobs.

**Responsibilities**:
- Store configuration options (queue, connection, delay, etc.)
- Provide getters and setters for all options
- Reset configuration after dispatch

**Properties**:
- `queue`: Queue name
- `connection`: Connection name
- `delay`: Job delay
- `withoutRelations`: Flag for relation serialization
- `shouldBeUnique`: Flag for unique jobs
- `shouldBeUniqueUntilProcessing`: Flag for unique until processing
- `shouldBeEncrypted`: Flag for encryption
- `rateLimiterName`: Rate limiter name
- `uniqueFor`: Duration for unique jobs

### 3. JobDispatcherService

**Location**: `src/Services/JobDispatcherService.php`

**Purpose**: Creates and dispatches the appropriate job class based on configuration.

**Responsibilities**:
- Create the correct job class (QueueFlowJob, UniqueQueueFlowJob, etc.)
- Apply configuration to the job
- Dispatch the job using Laravel's dispatch helper
- Apply rate limiting middleware

**Flow**:
1. Determine which job class to use based on configuration
2. Create job instance with the closure
3. Apply queue, connection, delay configurations
4. Dispatch the job
5. Apply rate limiting if configured

### 4. Job Classes

#### QueueFlowJob (Base)

**Location**: `src/Jobs/QueueFlowJob.php`

**Purpose**: Base job class that executes closures.

**Features**:
- Uses `SerializableClosure` to serialize closures
- Implements Laravel's `ShouldQueue` interface
- Supports `withoutRelations()` functionality
- Custom serialization logic

#### UniqueQueueFlowJob

**Location**: `src/Jobs/UniqueQueueFlowJob.php`

**Purpose**: Extends QueueFlowJob to implement unique job functionality.

**Features**:
- Implements `ShouldBeUnique` interface
- Generates unique ID based on closure content
- Configurable unique duration

#### UniqueUntilProcessingQueueFlowJob

**Location**: `src/Jobs/UniqueUntilProcessingQueueFlowJob.php`

**Purpose**: Extends QueueFlowJob for unique until processing functionality.

**Features**:
- Implements `ShouldBeUniqueUntilProcessing` interface
- Job is unique only until it starts processing

#### EncryptedQueueFlowJob

**Location**: `src/Jobs/EncryptedQueueFlowJob.php`

**Purpose**: Extends QueueFlowJob to encrypt job payload.

**Features**:
- Implements `ShouldBeEncrypted` interface
- Laravel automatically encrypts/decrypts the payload

### 5. QueueFlowServiceProvider

**Location**: `src/QueueFlowServiceProvider.php`

**Purpose**: Laravel service provider for package integration.

**Responsibilities**:
- Register package services
- Merge configuration
- Publish configuration file
- Bind Queue to container

## Flow Diagram

```
User Code
    ↓
Queue::add(closure)
    ↓
Queue::delay() / onQueue() / etc. (Configuration methods)
    ↓ (stores in)
QueueConfigurationService
    ↓
Queue::dispatch()
    ↓
JobDispatcherService::dispatch()
    ↓
JobDispatcherService::createJob() (based on configuration)
    ↓
QueueFlowJob / UniqueQueueFlowJob / EncryptedQueueFlowJob
    ↓
JobDispatcherService::applyConfiguration()
    ↓
Laravel's dispatch() helper
    ↓
Laravel Queue System
    ↓
Job Worker executes QueueFlowJob::handle()
    ↓
Closure is executed
```

## Configuration Flow

1. User calls configuration methods on `Queue`
2. Each method stores the configuration in `QueueConfigurationService`
3. Methods return `$this` for method chaining
4. When `dispatch()` is called, configuration is read from the service
5. Configuration is applied to the job before dispatching
6. Configuration is reset after dispatch

## Job Selection Logic

The `JobDispatcherService` selects the appropriate job class based on priority:

1. If `shouldBeUnique()` → `UniqueQueueFlowJob`
2. Else if `shouldBeUniqueUntilProcessing()` → `UniqueUntilProcessingQueueFlowJob`
3. Else if `shouldBeEncrypted()` → `EncryptedQueueFlowJob`
4. Else → `QueueFlowJob`

**Note**: Currently, only one special interface can be applied at a time. Future versions may support combinations.

## Closure Serialization

Closures are serialized using Laravel's `SerializableClosure` package:

1. Closure is wrapped in `SerializableClosure` in the constructor
2. When job is serialized, `SerializableClosure` handles the closure serialization
3. When job is unserialized, closure is restored
4. `handle()` method retrieves the closure and executes it

## Rate Limiting

Rate limiting is applied via Laravel's queue middleware:

1. User calls `rateLimited('limiter-name')`
2. Configuration is stored in `QueueConfigurationService`
3. During dispatch, `JobDispatcherService` applies `RateLimited` middleware
4. Laravel's queue system enforces the rate limit

## Auto-dispatch Feature

The `__destruct()` method in `Queue` automatically dispatches pending jobs:

1. If a closure was added but not dispatched
2. When the object is destroyed (goes out of scope)
3. The job is automatically dispatched

This allows for simpler syntax:
```php
$queue->add(fn() => doSomething());
// Auto-dispatches when $queue goes out of scope
```

## Extension Points

### Adding New Job Types

To add a new job type (e.g., `ShouldBeDelayed`):

1. Create new job class extending `QueueFlowJob`
2. Implement the desired interface
3. Add configuration method to `QueueConfigurationService`
4. Add fluent method to `Queue`
5. Update `JobDispatcherService::createJob()` logic

### Custom Middleware

Rate limiting uses middleware. You can extend this pattern:

1. Add configuration for custom middleware
2. Apply middleware in `JobDispatcherService::applyRateLimiting()`

## Testing Strategy

- **Unit Tests**: Test each service class independently
- **Integration Tests**: Test the full flow from `Queue` to job dispatch
- **Feature Tests**: Test in a real Laravel application context

## Performance Considerations

- Configuration is stored in memory (no database queries)
- Job selection is done via simple conditionals
- Closure serialization has overhead (use for small closures)
- Rate limiting adds minimal overhead

## Security Considerations

- Closures can contain sensitive data (use `shouldBeEncrypted()`)
- Unique job IDs are based on closure content (MD5 hash)
- Rate limiting prevents abuse
- Laravel's queue security features apply (encryption, authentication)
