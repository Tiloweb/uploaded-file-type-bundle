# Contributing to UploadedFileType Bundle

First off, thank you for considering contributing to this project! 🎉

## Code of Conduct

This project and everyone participating in it is governed by our commitment to providing a welcoming and inclusive environment. Please be respectful and constructive in all interactions.

## How Can I Contribute?

### 🐛 Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates.

When you create a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** (code snippets, configuration files)
- **Describe the behavior you observed and what you expected**
- **Include your environment details:**
  - PHP version
  - Symfony version
  - Bundle version
  - Operating system

### 💡 Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the suggested enhancement
- **Explain why this enhancement would be useful**
- **Include code examples** if applicable

### 📝 Pull Requests

1. **Fork the repository** and create your branch from `main`:

   ```bash
   git checkout -b feature/my-new-feature
   ```

2. **Install dependencies:**

   ```bash
   composer install
   ```

3. **Make your changes** following our coding standards

4. **Add or update tests** for your changes:

   ```bash
   composer test
   ```

5. **Ensure the test suite passes:**

   ```bash
   composer quality
   ```

6. **Commit your changes** with a descriptive commit message:

   ```bash
   git commit -m "feat: add support for custom mime types"
   ```

7. **Push to your fork** and submit a pull request

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer

### Installation

```bash
git clone https://github.com/Tiloweb/uploaded-file-type-bundle.git
cd uploaded-file-type-bundle
composer install
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Unit/UploadedFileTypeServiceTest.php

# Run with coverage
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/
```

### Code Quality

```bash
# Run all quality checks
composer quality

# Run PHPStan static analysis
composer phpstan

# Check coding standards
composer cs-check

# Fix coding standards automatically
composer cs-fix
```

## Coding Standards

This project follows the [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html) with some additions:

- **Strict types**: All PHP files must declare `strict_types=1`
- **Final classes**: Services and extensions should be `final`
- **Type declarations**: Use type hints for all parameters and return types
- **PHPDoc**: Only add PHPDoc when it provides additional information

### Example

```php
<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle;

final class MyService
{
    public function __construct(
        private readonly SomeDependency $dependency,
    ) {
    }

    /**
     * @param array<string, mixed> $options Additional options
     */
    public function doSomething(string $value, array $options = []): string
    {
        // Implementation
    }
}
```

## Commit Message Convention

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks

Examples:

```
feat: add support for custom filename sanitization
fix: handle empty file extensions correctly
docs: update README with S3 configuration example
test: add tests for delete functionality
```

## Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring

## Pull Request Process

1. Update the README.md or documentation if needed
2. Update the CHANGELOG.md with your changes
3. The PR will be merged once you have the approval of a maintainer

## Questions?

Feel free to open an issue with your question or reach out to the maintainers.

Thank you for contributing! 🙌
