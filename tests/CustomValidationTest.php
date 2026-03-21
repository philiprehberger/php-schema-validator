<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Tests;

use PhilipRehberger\SchemaValidator\Schema;
use PHPUnit\Framework\TestCase;

class CustomValidationTest extends TestCase
{
    // ---------------------------------------------------------------
    // custom() — string schema
    // ---------------------------------------------------------------

    public function test_custom_string_passes_when_validator_returns_null(): void
    {
        $schema = Schema::string()->custom(fn (string $v): ?string => null);

        $this->assertEmpty($schema->validate('anything', 'field'));
    }

    public function test_custom_string_fails_when_validator_returns_error(): void
    {
        $schema = Schema::string()->custom(
            fn (string $v): ?string => str_contains($v, ' ') ? 'must not contain spaces' : null,
        );

        $this->assertEmpty($schema->validate('hello', 'field'));
        $this->assertContains('field must not contain spaces', $schema->validate('hello world', 'field'));
    }

    // ---------------------------------------------------------------
    // custom() — int schema
    // ---------------------------------------------------------------

    public function test_custom_int_passes_when_validator_returns_null(): void
    {
        $schema = Schema::int()->custom(fn (int $v): ?string => null);

        $this->assertEmpty($schema->validate(42, 'count'));
    }

    public function test_custom_int_fails_when_validator_returns_error(): void
    {
        $schema = Schema::int()->custom(
            fn (int $v): ?string => $v % 2 !== 0 ? 'must be even' : null,
        );

        $this->assertEmpty($schema->validate(4, 'count'));
        $this->assertContains('count must be even', $schema->validate(3, 'count'));
    }

    // ---------------------------------------------------------------
    // custom() — float schema
    // ---------------------------------------------------------------

    public function test_custom_float_fails_when_validator_returns_error(): void
    {
        $schema = Schema::float()->custom(
            fn (float $v): ?string => $v === 0.0 ? 'must not be zero' : null,
        );

        $this->assertEmpty($schema->validate(1.5, 'price'));
        $this->assertContains('price must not be zero', $schema->validate(0.0, 'price'));
    }

    // ---------------------------------------------------------------
    // custom() — bool schema
    // ---------------------------------------------------------------

    public function test_custom_bool_fails_when_validator_returns_error(): void
    {
        $schema = Schema::bool()->custom(
            fn (bool $v): ?string => $v === false ? 'must be true' : null,
        );

        $this->assertEmpty($schema->validate(true, 'active'));
        $this->assertContains('active must be true', $schema->validate(false, 'active'));
    }

    // ---------------------------------------------------------------
    // custom() — enum schema
    // ---------------------------------------------------------------

    public function test_custom_enum_fails_when_validator_returns_error(): void
    {
        $schema = Schema::enum(['admin', 'editor', 'viewer'])->custom(
            fn (string $v): ?string => $v === 'viewer' ? 'insufficient role' : null,
        );

        $this->assertEmpty($schema->validate('admin', 'role'));
        $this->assertContains('role insufficient role', $schema->validate('viewer', 'role'));
    }

    // ---------------------------------------------------------------
    // custom() — array schema
    // ---------------------------------------------------------------

    public function test_custom_array_fails_when_validator_returns_error(): void
    {
        $schema = Schema::arrayOf(Schema::string())->custom(
            fn (array $v): ?string => count($v) < 2 ? 'must have at least 2 items' : null,
        );

        $this->assertEmpty($schema->validate(['a', 'b'], 'tags'));
        $this->assertContains('tags must have at least 2 items', $schema->validate(['a'], 'tags'));
    }

    // ---------------------------------------------------------------
    // custom() — any schema
    // ---------------------------------------------------------------

    public function test_custom_any_fails_when_validator_returns_error(): void
    {
        $schema = Schema::any()->custom(
            fn (mixed $v): ?string => is_string($v) ? null : 'expected string',
        );

        $this->assertEmpty($schema->validate('ok', 'data'));
        $this->assertContains('data expected string', $schema->validate(42, 'data'));
    }

    // ---------------------------------------------------------------
    // custom() — object schema
    // ---------------------------------------------------------------

    public function test_custom_object_runs_after_field_validation_passes(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
        ])->custom(
            fn (array $v): ?string => strlen($v['name']) > 5 ? 'name too long for custom rule' : null,
        );

        $result = $schema->validateData(['name' => 'Alice']);
        $this->assertTrue($result->passes());

        $result = $schema->validateData(['name' => 'Alexander']);
        $this->assertTrue($result->fails());
        $this->assertContains('value name too long for custom rule', $result->errors());
    }

    public function test_custom_object_does_not_run_when_field_validation_fails(): void
    {
        $called = false;
        $schema = Schema::object([
            'name' => Schema::string(),
        ])->custom(function (array $v) use (&$called): ?string {
            $called = true;

            return null;
        });

        $schema->validateData([]);

        $this->assertFalse($called);
    }

    // ---------------------------------------------------------------
    // transform() — string schema
    // ---------------------------------------------------------------

    public function test_transform_string_trims_before_validation(): void
    {
        $schema = Schema::string()->min(3)->transform(fn (mixed $v): string => trim((string) $v));

        $this->assertEmpty($schema->validate('  abc  ', 'name'));
    }

    public function test_transform_string_lowercases_before_validation(): void
    {
        $schema = Schema::string()->regex('/^[a-z]+$/')->transform(fn (mixed $v): string => strtolower((string) $v));

        $this->assertEmpty($schema->validate('HELLO', 'slug'));
    }

    // ---------------------------------------------------------------
    // transform() — int schema
    // ---------------------------------------------------------------

    public function test_transform_int_coerces_string_to_int(): void
    {
        $schema = Schema::int()->min(1)->transform(fn (mixed $v): int => (int) $v);

        $this->assertEmpty($schema->validate('42', 'count'));
    }

    public function test_transform_int_applies_before_min_max(): void
    {
        $schema = Schema::int()->min(0)->max(100)->transform(fn (mixed $v): int => abs((int) $v));

        $this->assertEmpty($schema->validate('-50', 'score'));
    }

    // ---------------------------------------------------------------
    // transform() — float schema
    // ---------------------------------------------------------------

    public function test_transform_float_rounds_value(): void
    {
        $schema = Schema::float()->max(10.0)->transform(fn (mixed $v): float => round((float) $v, 2));

        $this->assertEmpty($schema->validate(9.999999, 'price'));
    }

    // ---------------------------------------------------------------
    // transform() — bool schema
    // ---------------------------------------------------------------

    public function test_transform_bool_coerces_truthy(): void
    {
        $schema = Schema::bool()->transform(fn (mixed $v): bool => (bool) $v);

        $this->assertEmpty($schema->validate(1, 'flag'));
        $this->assertEmpty($schema->validate(0, 'flag'));
    }

    // ---------------------------------------------------------------
    // transform() — enum schema
    // ---------------------------------------------------------------

    public function test_transform_enum_normalizes_value(): void
    {
        $schema = Schema::enum(['active', 'inactive'])->transform(fn (mixed $v): string => strtolower((string) $v));

        $this->assertEmpty($schema->validate('ACTIVE', 'status'));
        $this->assertNotEmpty($schema->validate('UNKNOWN', 'status'));
    }

    // ---------------------------------------------------------------
    // transform() — object schema
    // ---------------------------------------------------------------

    public function test_transform_object_normalizes_data(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
        ])->transform(fn (mixed $v): array => array_change_key_case((array) $v, CASE_LOWER));

        $result = $schema->validateData(['NAME' => 'Alice']);
        $this->assertTrue($result->passes());
    }

    // ---------------------------------------------------------------
    // transform() does not run on null
    // ---------------------------------------------------------------

    public function test_transform_does_not_run_on_null_when_nullable(): void
    {
        $called = false;
        $schema = Schema::string()->nullable()->transform(function (mixed $v) use (&$called): string {
            $called = true;

            return (string) $v;
        });

        $errors = $schema->validate(null, 'field');

        $this->assertEmpty($errors);
        $this->assertFalse($called);
    }

    // ---------------------------------------------------------------
    // transform() + custom() combined
    // ---------------------------------------------------------------

    public function test_transform_runs_before_custom(): void
    {
        $schema = Schema::string()
            ->transform(fn (mixed $v): string => trim((string) $v))
            ->custom(fn (string $v): ?string => $v === '' ? 'must not be blank' : null);

        $this->assertContains('field must not be blank', $schema->validate('   ', 'field'));
        $this->assertEmpty($schema->validate('  hello  ', 'field'));
    }

    // ---------------------------------------------------------------
    // crossField() — single validator
    // ---------------------------------------------------------------

    public function test_cross_field_passes_when_validator_returns_null(): void
    {
        $schema = Schema::object([
            'password' => Schema::string()->min(6),
            'password_confirmation' => Schema::string(),
        ])->crossField(
            fn (array $data): ?string => $data['password'] === $data['password_confirmation']
                ? null
                : 'passwords must match',
        );

        $result = $schema->validateData([
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertTrue($result->passes());
    }

    public function test_cross_field_fails_when_validator_returns_error(): void
    {
        $schema = Schema::object([
            'password' => Schema::string()->min(6),
            'password_confirmation' => Schema::string(),
        ])->crossField(
            fn (array $data): ?string => $data['password'] === $data['password_confirmation']
                ? null
                : 'passwords must match',
        );

        $result = $schema->validateData([
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ]);

        $this->assertTrue($result->fails());
        $this->assertContains('passwords must match', $result->errors());
    }

    // ---------------------------------------------------------------
    // crossField() — multiple validators
    // ---------------------------------------------------------------

    public function test_cross_field_supports_multiple_validators(): void
    {
        $schema = Schema::object([
            'start' => Schema::int(),
            'end' => Schema::int(),
            'min_range' => Schema::int(),
        ])->crossField(
            fn (array $d): ?string => $d['end'] > $d['start'] ? null : 'end must be after start',
        )->crossField(
            fn (array $d): ?string => ($d['end'] - $d['start']) >= $d['min_range'] ? null : 'range too small',
        );

        $result = $schema->validateData(['start' => 1, 'end' => 10, 'min_range' => 5]);
        $this->assertTrue($result->passes());

        $result = $schema->validateData(['start' => 1, 'end' => 10, 'min_range' => 20]);
        $this->assertTrue($result->fails());
        $this->assertContains('range too small', $result->errors());

        $result = $schema->validateData(['start' => 10, 'end' => 5, 'min_range' => 1]);
        $this->assertTrue($result->fails());
        $this->assertContains('end must be after start', $result->errors());
    }

    // ---------------------------------------------------------------
    // crossField() does not run when field validation fails
    // ---------------------------------------------------------------

    public function test_cross_field_does_not_run_when_field_validation_fails(): void
    {
        $called = false;
        $schema = Schema::object([
            'start' => Schema::int(),
            'end' => Schema::int(),
        ])->crossField(function (array $data) use (&$called): ?string {
            $called = true;

            return null;
        });

        $schema->validateData(['start' => 'not-int', 'end' => 10]);

        $this->assertFalse($called);
    }

    // ---------------------------------------------------------------
    // crossField() + custom() combined on object
    // ---------------------------------------------------------------

    public function test_cross_field_and_custom_both_run_on_valid_data(): void
    {
        $schema = Schema::object([
            'a' => Schema::int(),
            'b' => Schema::int(),
        ])->crossField(
            fn (array $d): ?string => $d['a'] < $d['b'] ? null : 'a must be less than b',
        )->custom(
            fn (array $d): ?string => ($d['a'] + $d['b']) > 100 ? 'sum too large' : null,
        );

        $result = $schema->validateData(['a' => 1, 'b' => 2]);
        $this->assertTrue($result->passes());

        $result = $schema->validateData(['a' => 60, 'b' => 50]);
        $this->assertTrue($result->fails());
        $this->assertContains('a must be less than b', $result->errors());

        $result = $schema->validateData(['a' => 40, 'b' => 70]);
        $this->assertTrue($result->fails());
        $this->assertContains('value sum too large', $result->errors());
    }
}
