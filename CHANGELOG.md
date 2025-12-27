# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2025-12-27

### Added

- **Symfony 8 compatibility** - Full support for the upcoming Symfony 8.0
- **PHP 8.5 compatibility** - Ready for PHP 8.5 when released
- `delete()` method on `UploadedFileTypeService` to remove files
- `exists()` method on `UploadedFileTypeService` to check file existence
- `getConfigurationNames()` method to list all available configurations
- `delete_previous` form option to control automatic file cleanup (default: `true`)
- Comprehensive test suite with PHPUnit
- PHPStan level 8 static analysis
- PHP-CS-Fixer configuration for consistent code style
- GitHub Actions CI/CD pipeline
- Symfony Flex recipe for easy installation

### Changed

- **BREAKING**: Minimum PHP version is now 8.1
- **BREAKING**: Minimum Symfony version is now 6.4 LTS
- **BREAKING**: Bundle now uses `AbstractBundle` instead of `Bundle` class
- **BREAKING**: Configuration file changed from XML to PHP format
- Improved filename generation with cryptographically secure random bytes
- Better error handling with specific exception messages
- Service is now `final` and uses constructor property promotion
- Form extension is now `final` for better performance

### Removed

- **BREAKING**: Removed support for PHP < 8.1
- **BREAKING**: Removed support for Symfony < 6.4
- Removed deprecated `getConfiguration()` fallback to array index 0

### Fixed

- Stream resource leak when upload fails
- Proper handling of files without extensions
- Edge cases in URL generation with trailing slashes

### Security

- Use `random_bytes()` instead of `md5(microtime())` for filename generation

## [1.0.0] - 2021-01-15

### Added

- Initial release
- Support for Symfony 5.x and 6.x
- Form type extension for FileType
- Flysystem integration via OneupFlysystemBundle
- Custom filename strategies
- Twig form theme with image preview

[Unreleased]: https://github.com/Tiloweb/uploaded-file-type-bundle/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/Tiloweb/uploaded-file-type-bundle/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/Tiloweb/uploaded-file-type-bundle/releases/tag/v1.0.0
