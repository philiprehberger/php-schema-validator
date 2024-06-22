# Changelog

All notable changes to `php-schema-validator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-03-27

### Added
- Conditional field validation via `ObjectSchema::when()`
- Schema composition via `ObjectSchema::extend()`
- Custom error messages via `ValidationResult::withMessages()`

## [1.1.4] - 2026-03-23

### Changed
- Standardize README requirements format per template guide

## [1.1.3] - 2026-03-23

### Fixed
- Remove decorative dividers from README for template compliance

## [1.1.2] - 2026-03-20

### Added
- Expanded test suite with dedicated tests for custom validation, transforms, cross-field validation, and type schema edge cases

## [1.1.1] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

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
