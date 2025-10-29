# Contributing to QueueFlow Laravel Queue

Thank you for considering contributing to QueueFlow Laravel Queue! This document outlines the process for contributing to this project.

## Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/b7s/laravel-queue-flow.git
   cd queue-flow
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Run tests**
   ```bash
   composer test
   # or
   vendor/bin/pest
   ```

## Coding Standards

- Follow PSR-12 coding standards
- Use PHP 8.3+ features (typed properties, match expressions, etc.)
- Use strict typing: `declare(strict_types=1);`
- Add type hints to all parameters and return types
- Write descriptive variable and method names

## Testing

- Write tests for all new features
- Ensure all tests pass before submitting a PR
- Aim for high code coverage
- Use Pest PHP for testing

## Pull Request Process

1. **Fork the repository** and create your branch from `main`
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**
   - Write clean, documented code
   - Add tests for new functionality
   - Update documentation if needed

3. **Commit your changes**
   ```bash
   git commit -m "Add feature: description"
   ```

4. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

5. **Open a Pull Request**
   - Provide a clear description of the changes
   - Reference any related issues
   - Ensure all tests pass

## Code Review Process

- All submissions require review
- Maintainers may request changes
- Once approved, your PR will be merged

## Reporting Bugs

When reporting bugs, please include:

- PHP version
- Laravel version
- Package version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Any error messages

## Feature Requests

We welcome feature requests! Please:

- Check if the feature already exists
- Provide a clear use case
- Explain why it would be useful
- Consider submitting a PR

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Maintain a positive environment

## Questions?

Feel free to open an issue for questions or discussions.

Thank you for contributing! 🚀
