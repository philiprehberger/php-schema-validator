# PHP Schema Validator

[![Tests](https://github.com/philiprehberger/php-schema-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-schema-validator/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-schema-validator.svg)](https://packagist.org/packages/philiprehberger/php-schema-validator)
[![Total Downloads](https://img.shields.io/packagist/dt/philiprehberger/php-schema-validator.svg)](https://packagist.org/packages/philiprehberger/php-schema-validator)
[![PHP Version Require](https://img.shields.io/packagist/php-v/philiprehberger/php-schema-validator.svg)](https://packagist.org/packages/philiprehberger/php-schema-validator)
[![License](https://img.shields.io/github/license/philiprehberger/php-schema-validator)](LICENSE)

Fluent data schema validator with nested objects, arrays, and dot-notation errors.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.2    |

No external dependencies required.

---

## Installation

```bash
composer require philiprehberger/php-schema-validator
```

---

## Usage

```php
use PhilipRehberger\SchemaValidator\Schema;

$schema = Schema::object([
    'name'  => Schema::string()->min(1)->max(100),
    'email' => Schema::string()->email(),
    'age'   => Schema::int()->min(0)->max(150),
]);

$result = $schema->validateData([
    'name'  => 'Alice',
    'email' => 'alice@example.com',
    'age'   => 30,
]);

$result->passes(); // true
$result->fails();  // false
$result->errors(); // []
```

### Nested Objects

```php
$schema = Schema::object([
    'user' => Schema::object([
        'profile' => Schema::object([
            'email' => Schema::string()->email(),
        ]),
    ]),
]);

$result = $schema->validateData([
    'user' => ['profile' => ['email' => 'invalid']],
]);

$result->errors(); // ["user.profile.email must be a valid email address"]
```

### Typed Arrays

```php
$schema = Schema::object([
    'tags'  => Schema::arrayOf(Schema::string()),
    'items' => Schema::arrayOf(Schema::object([
        'id'   => Schema::int(),
        'name' => Schema::string(),
    ])),
]);
```

### Optional and Nullable Fields

```php
$schema = Schema::object([
    'name'     => Schema::string(),
    'nickname' => Schema::string()->optional(),  // field may be absent
    'bio'      => Schema::string()->nullable(),  // field may be null
]);
```

### String Validators

```php
Schema::string()->min(3)->max(50);   // length constraints
Schema::string()->email();           // email format
Schema::string()->url();             // URL format
Schema::string()->uuid();            // UUID format
Schema::string()->regex('/^\d+$/');  // custom pattern
```

### Enum Values

```php
Schema::enum(['draft', 'published', 'archived']);
```

### Any Value

```php
Schema::any();           // accepts any non-null value
Schema::any()->nullable(); // accepts anything including null
```

---

## API

### `Schema` (static factory)

| Method | Returns | Description |
|--------|---------|-------------|
| `Schema::object(array $fields)` | `ObjectSchema` | Create an object schema with field definitions |
| `Schema::string()` | `StringSchema` | Create a string schema |
| `Schema::int()` | `IntSchema` | Create an integer schema |
| `Schema::float()` | `FloatSchema` | Create a float schema |
| `Schema::bool()` | `BoolSchema` | Create a boolean schema |
| `Schema::arrayOf(SchemaType $item)` | `ArraySchema` | Create a typed array schema |
| `Schema::enum(array $values)` | `EnumSchema` | Create an enum schema |
| `Schema::any()` | `AnySchema` | Create a schema that accepts any value |

### `ValidationResult`

| Method | Returns | Description |
|--------|---------|-------------|
| `passes()` | `bool` | True if validation passed |
| `fails()` | `bool` | True if validation failed |
| `errors()` | `array<string>` | All error messages |
| `firstError()` | `?string` | First error message or null |

### Common Modifiers

All schema types support:

| Method | Description |
|--------|-------------|
| `optional()` | Field may be absent from the parent object |
| `nullable()` | Field may be null |

---

## Testing

```bash
composer install
vendor/bin/phpunit
```

Code style:

```bash
vendor/bin/pint
```

Static analysis:

```bash
vendor/bin/phpstan analyse
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
