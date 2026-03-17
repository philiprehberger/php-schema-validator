# Changelog

All notable changes to `php-schema-validator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-03-16

### Added
- `custom(callable)` method on all schema types for user-defined validation rules
- `transform(callable)` method on all schema types for pre-validation value transformation
- `crossField(callable)` method on `ObjectSchema` for cross-field validation
- `HasCustomValidation` trait in `Concerns\` providing shared custom/transform logic

## [1.0.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.1] - 2026-03-15

### Changed
- Standardize README badges

## [1.0.0] - 2026-03-15

### Added
- Initial release
- Fluent schema builder with `Schema::object()`, `Schema::string()`, `Schema::int()`, `Schema::float()`, `Schema::bool()`, `Schema::arrayOf()`, `Schema::enum()`, `Schema::any()`
- String validation with `min`, `max`, `email`, `url`, `uuid`, `regex`
- Int and float validation with `min` and `max` constraints
- Nested object validation with dot-notation error paths
- Typed array validation via `Schema::arrayOf()`
- Enum validation for allowed values
- `optional()` and `nullable()` modifiers on all types
- `ValidationResult` with `passes()`, `fails()`, `errors()`, and `firstError()`
