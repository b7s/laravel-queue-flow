# Documentation

Welcome to the QueueFlow Laravel Queue documentation!

## Table of Contents

### Getting Started
- **[Installation Guide](INSTALLATION.md)** - Step-by-step installation instructions
- **[Quick Start](../README.md#usage)** - Get started quickly with basic examples

### In-Depth Documentation
- **[Architecture](ARCHITECTURE.md)** - Detailed architecture and design patterns
- **[Usage Examples](USAGE_EXAMPLES.md)** - Practical examples for common scenarios
- **[Project Summary](PROJECT_SUMMARY.md)** - Complete project overview

### Contributing
- **[Contributing Guide](../CONTRIBUTING.md)** - How to contribute to the project
- **[Publishing Guide](PUBLISHING.md)** - How to publish to Packagist

### Reference
- **[Changelog](../CHANGELOG.md)** - Version history and changes
- **[License](../LICENSE)** - MIT License details

## Quick Links

### Installation
```bash
composer require b7s/laravel-queue-flow
```

### Basic Usage
```php
use B7s\QueueFlow\Queue;

$queue = new Queue();
$queue
    ->add(fn () => doSomething())
    ->delay(now()->addMinutes(10))
    ->dispatch();
```

## Documentation Structure

```
docs/
├── README.md              # This file - Documentation index
├── INSTALLATION.md        # Installation and setup guide
├── ARCHITECTURE.md        # Architecture and design documentation
├── USAGE_EXAMPLES.md      # Practical usage examples
├── PROJECT_SUMMARY.md     # Complete project overview
└── PUBLISHING.md          # Guide for publishing to Packagist
```

## Features Overview

- ✅ **Fluent API** - Chain methods for easy configuration
- ✅ **No Job Classes** - Execute closures directly
- ✅ **Delayed Jobs** - Schedule for future execution
- ✅ **Unique Jobs** - Prevent duplicates
- ✅ **Encryption** - Secure sensitive data
- ✅ **Rate Limiting** - Control execution rate
- ✅ **Type Safe** - Full PHP 8.2+ type hints
- ✅ **Laravel 11+** - Compatible with latest Laravel

## Support

- **GitHub Issues**: [Report bugs or request features](https://github.com/b7s/laravel-queue-flow/issues)
- **Documentation**: You're reading it!
- **Examples**: See [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md)

## Contributing

We welcome contributions! Please read our [Contributing Guide](../CONTRIBUTING.md) for details.

## License

MIT License - See [LICENSE](../LICENSE) for details.

---

**Need help?** Check the [Installation Guide](INSTALLATION.md) or [Usage Examples](USAGE_EXAMPLES.md)!
