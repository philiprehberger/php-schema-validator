# PHP Schema Validator

[![Tests](https://github.com/philiprehberger/php-schema-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-schema-validator/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/philiprehberger/php-schema-validator.svg)](https://packagist.org/packages/philiprehberger/php-schema-validator)
[![GitHub Release](https://img.shields.io/github/v/release/philiprehberger/php-schema-validator)](https://github.com/philiprehberger/php-schema-validator/releases)
[![Last Updated](https://img.shields.io/github/last-commit/philiprehberger/php-schema-validator)](https://github.com/philiprehberger/php-schema-validator/commits/main)
[![License](https://img.shields.io/github/license/philiprehberger/php-schema-validator)](LICENSE)
[![Bug Reports](https://img.shields.io/github/issues/philiprehberger/php-schema-validator/bug)](https://github.com/philiprehberger/php-schema-validator/issues?q=label%3Abug)
[![Feature Requests](https://img.shields.io/github/issues/philiprehberger/php-schema-validator/enhancement)](https://github.com/philiprehberger/php-schema-validator/issues?q=label%3Aenhancement)
[![Sponsor](https://img.shields.io/badge/sponsor-GitHub%20Sponsors-ec6cb9)](https://github.com/sponsors/philiprehberger)

Fluent data schema validator with nested objects, arrays, and dot-notation errors.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-schema-validator
```

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

### Custom Validation Rules

Add custom validation logic to any schema type using `custom()`. The callable receives the value and returns `null` if valid, or an error message string if invalid.

```php
$schema = Schema::object([
    'username' => Schema::string()->min(3)->custom(function (string $value): ?string {
        if (str_starts_with($value, 'admin')) {
            return 'must not start with "admin"';
        }

        return null;
    }),
    'age' => Schema::int()->min(0)->custom(function (int $value): ?string {
        if ($value % 2 !== 0) {
            return 'must be an even number';
        }

        return null;
    }),
]);

$result = $schema->validateData(['username' => 'admin_user', 'age' => 25]);
$result->errors();
// ["username must not start with "admin"", "age must be an even number"]
```

Custom validators only run when all built-in checks pass.

### Value Transformers

Use `transform()` to normalize a value before validation. The callable receives the raw value and returns the transformed value.

```php
$schema = Schema::object([
    'email' => Schema::string()->email()->transform(fn (mixed $v) => strtolower(trim($v))),
    'tags'  => Schema::arrayOf(Schema::string())->transform(fn (mixed $v) => array_unique($v)),
]);

$result = $schema->validateData([
    'email' => '  Alice@Example.COM  ',
    'tags'  => ['php', 'laravel', 'php'],
]);

$result->passes(); // true — email was trimmed and lowered before validation
```

Transformers run before any type or constraint checks (but after the null check).

### Cross-Field Validation

Use `crossField()` on an `ObjectSchema` to validate relationships between fields. Each callable receives the full data array and returns `null` if valid, or an error message string.

```php
$schema = Schema::object([
    'password'         => Schema::string()->min(8),
    'password_confirm' => Schema::string(),
    'start_date'       => Schema::string(),
    'end_date'         => Schema::string(),
])->crossField(function (array $data): ?string {
    if ($data['password'] !== $data['password_confirm']) {
        return 'password_confirm must match password';
    }

    return null;
})->crossField(function (array $data): ?string {
    if ($data['start_date'] >= $data['end_date']) {
        return 'end_date must be after start_date';
    }

    return null;
});

$result = $schema->validateData([
    'password'         => 'secret123',
    'password_confirm' => 'different',
    'start_date'       => '2026-03-20',
    'end_date'         => '2026-03-10',
]);

$result->errors();
// ["password_confirm must match password", "end_date must be after start_date"]
```

Cross-field validators only run when all individual field validations pass.

### Conditional Fields

Use `when()` on an `ObjectSchema` to conditionally require additional fields based on the value of another field.

```php
$schema = Schema::object([
    'type' => Schema::string(),
    'email' => Schema::string()->email(),
])->when('type', 'business', [
    'company_name' => Schema::string()->min(1),
    'tax_id' => Schema::string(),
]);

// When type is 'business', company_name and tax_id are also validated
// When type is anything else, those fields are ignored
```

### Schema Composition

Use `extend()` to create a new schema that combines the fields of the current schema with additional fields.

```php
$base = Schema::object(['name' => Schema::string(), 'email' => Schema::string()->email()]);
$admin = $base->extend(['role' => Schema::string(), 'permissions' => Schema::arrayOf(Schema::string())]);
// $admin validates name, email, role, and permissions
// $base is unchanged
```

### Custom Error Messages

Use `withMessages()` on a `ValidationResult` to replace default error messages for specific fields.

```php
$result = $schema->validateData($data);

$result = $result->withMessages([
    'name' => 'Please enter your full name',
    'email' => 'A valid email address is required',
]);
```

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
| `withMessages(array $messages)` | `ValidationResult` | Replace errors for matching field paths with custom messages |

### `ObjectSchema` extras

| Method | Description |
|--------|-------------|
| `crossField(callable $validator)` | Add a cross-field validator (receives full data array, returns `?string`) |
| `when(string $field, mixed $value, array $thenSchema)` | Conditionally validate additional fields when a field matches a value |
| `extend(array $additionalFields)` | Create a new schema combining current fields with additional fields |

### Common Modifiers

All schema types support:

| Method | Description |
|--------|-------------|
| `optional()` | Field may be absent from the parent object |
| `nullable()` | Field may be null |
| `custom(callable $validator)` | Add a custom validation callback (receives value, returns `?string`) |
| `transform(callable $transformer)` | Transform the value before validation |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
```

## Support

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Philip%20Rehberger-blue?logo=linkedin)](https://www.linkedin.com/in/philiprehberger)
[![Packages](https://img.shields.io/badge/More%20Packages-philiprehberger-orange?logo=github)](https://github.com/philiprehberger/packages)

## License

[MIT](LICENSE)
