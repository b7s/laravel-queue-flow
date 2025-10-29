# Changelog

All notable changes to `queue-flow` will be documented in this file.

## [1.0.0] - 2025-10-29

### Added
- Initial release
- Fluent API for queue management
- Support for delayed jobs
- Support for unique jobs (`shouldBeUnique()`)
- Support for unique until processing jobs (`shouldBeUniqueUntilProcessing()`)
- Support for encrypted jobs (`shouldBeEncrypted()`)
- Support for rate limiting (`rateLimited()`)
- Support for `withoutRelations()` to prevent relation serialization
- Custom queue and connection support
- Auto-dispatch on object destruction
- Configuration file with sensible defaults
- Service Provider for Laravel integration
- Comprehensive documentation

### Features
- **Queue**: Main class for queue management
- **QueueConfigurationService**: Manages queue configuration state
- **JobDispatcherService**: Handles job creation and dispatching
- **QueueFlowJob**: Base job class for closure execution
- **UniqueQueueFlowJob**: Job class implementing ShouldBeUnique
- **UniqueUntilProcessingQueueFlowJob**: Job class implementing ShouldBeUniqueUntilProcessing
- **EncryptedQueueFlowJob**: Job class implementing ShouldBeEncrypted

### Requirements
- PHP 8.3+
- Laravel 11.0+
