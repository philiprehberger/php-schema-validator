<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Tests;

use PhilipRehberger\SchemaValidator\Schema;
use PHPUnit\Framework\TestCase;

class SchemaValidatorTest extends TestCase
{
    public function test_string_validates_valid_string(): void
    {
        $schema = Schema::string();
        $errors = $schema->validate('hello');

        $this->assertEmpty($errors);
    }

    public function test_string_rejects_non_string(): void
    {
        $schema = Schema::string();
        $errors = $schema->validate(123, 'name');

        $this->assertContains('name must be a string', $errors);
    }

    public function test_string_min_and_max(): void
    {
        $schema = Schema::string()->min(3)->max(10);

        $this->assertNotEmpty($schema->validate('ab', 'name'));
        $this->assertEmpty($schema->validate('abc', 'name'));
        $this->assertNotEmpty($schema->validate('a very long string here', 'name'));
    }

    public function test_string_email_validation(): void
    {
        $schema = Schema::string()->email();

        $this->assertEmpty($schema->validate('user@example.com', 'email'));
        $this->assertNotEmpty($schema->validate('not-an-email', 'email'));
    }

    public function test_string_url_validation(): void
    {
        $schema = Schema::string()->url();

        $this->assertEmpty($schema->validate('https://example.com', 'url'));
        $this->assertNotEmpty($schema->validate('not-a-url', 'url'));
    }

    public function test_string_uuid_validation(): void
    {
        $schema = Schema::string()->uuid();

        $this->assertEmpty($schema->validate('550e8400-e29b-41d4-a716-446655440000', 'id'));
        $this->assertNotEmpty($schema->validate('not-a-uuid', 'id'));
    }

    public function test_string_regex_validation(): void
    {
        $schema = Schema::string()->regex('/^\d{3}-\d{4}$/');

        $this->assertEmpty($schema->validate('123-4567', 'code'));
        $this->assertNotEmpty($schema->validate('abc', 'code'));
    }

    public function test_int_validates_valid_integer(): void
    {
        $schema = Schema::int()->min(1)->max(100);

        $this->assertEmpty($schema->validate(50, 'age'));
        $this->assertNotEmpty($schema->validate(0, 'age'));
        $this->assertNotEmpty($schema->validate(101, 'age'));
    }

    public function test_int_rejects_non_integer(): void
    {
        $schema = Schema::int();

        $this->assertNotEmpty($schema->validate('string', 'count'));
        $this->assertNotEmpty($schema->validate(3.14, 'count'));
    }

    public function test_float_validates_valid_float(): void
    {
        $schema = Schema::float()->min(0.0)->max(100.0);

        $this->assertEmpty($schema->validate(50.5, 'price'));
        $this->assertEmpty($schema->validate(50, 'price'));
        $this->assertNotEmpty($schema->validate(-1.0, 'price'));
    }

    public function test_bool_validates_boolean(): void
    {
        $schema = Schema::bool();

        $this->assertEmpty($schema->validate(true, 'active'));
        $this->assertEmpty($schema->validate(false, 'active'));
        $this->assertNotEmpty($schema->validate(1, 'active'));
        $this->assertNotEmpty($schema->validate('true', 'active'));
    }

    public function test_enum_validates_allowed_values(): void
    {
        $schema = Schema::enum(['draft', 'published', 'archived']);

        $this->assertEmpty($schema->validate('draft', 'status'));
        $this->assertNotEmpty($schema->validate('deleted', 'status'));
    }

    public function test_object_validates_flat_object(): void
    {
        $schema = Schema::object([
            'name' => Schema::string()->min(1),
            'age' => Schema::int()->min(0),
        ]);

        $result = $schema->validateData([
            'name' => 'Alice',
            'age' => 30,
        ]);

        $this->assertTrue($result->passes());
        $this->assertFalse($result->fails());
        $this->assertEmpty($result->errors());
    }

    public function test_object_reports_missing_required_fields(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
            'email' => Schema::string()->email(),
        ]);

        $result = $schema->validateData([]);

        $this->assertTrue($result->fails());
        $this->assertContains('name is required', $result->errors());
        $this->assertContains('email is required', $result->errors());
    }

    public function test_object_allows_optional_fields(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
            'nickname' => Schema::string()->optional(),
        ]);

        $result = $schema->validateData(['name' => 'Alice']);

        $this->assertTrue($result->passes());
    }

    public function test_nested_object_with_dot_notation_errors(): void
    {
        $schema = Schema::object([
            'user' => Schema::object([
                'profile' => Schema::object([
                    'email' => Schema::string()->email(),
                ]),
            ]),
        ]);

        $result = $schema->validateData([
            'user' => [
                'profile' => [
                    'email' => 'invalid',
                ],
            ],
        ]);

        $this->assertTrue($result->fails());
        $this->assertContains('user.profile.email must be a valid email address', $result->errors());
    }

    public function test_array_of_strings(): void
    {
        $schema = Schema::object([
            'tags' => Schema::arrayOf(Schema::string()),
        ]);

        $result = $schema->validateData(['tags' => ['php', 'validation']]);
        $this->assertTrue($result->passes());

        $result = $schema->validateData(['tags' => ['php', 123]]);
        $this->assertTrue($result->fails());
        $this->assertContains('tags[1] must be a string', $result->errors());
    }

    public function test_array_of_objects(): void
    {
        $schema = Schema::object([
            'items' => Schema::arrayOf(Schema::object([
                'id' => Schema::int(),
                'name' => Schema::string(),
            ])),
        ]);

        $result = $schema->validateData([
            'items' => [
                ['id' => 1, 'name' => 'First'],
                ['id' => 2, 'name' => 'Second'],
            ],
        ]);

        $this->assertTrue($result->passes());
    }

    public function test_nullable_fields(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
            'bio' => Schema::string()->nullable(),
        ]);

        $result = $schema->validateData(['name' => 'Alice', 'bio' => null]);
        $this->assertTrue($result->passes());

        $result = $schema->validateData(['name' => 'Alice', 'bio' => 'Hello']);
        $this->assertTrue($result->passes());
    }

    public function test_any_schema_accepts_any_value(): void
    {
        $schema = Schema::object([
            'metadata' => Schema::any(),
        ]);

        $this->assertTrue($schema->validateData(['metadata' => 'string'])->passes());
        $this->assertTrue($schema->validateData(['metadata' => 42])->passes());
        $this->assertTrue($schema->validateData(['metadata' => true])->passes());
    }

    public function test_validation_result_first_error(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
            'email' => Schema::string()->email(),
        ]);

        $result = $schema->validateData([]);

        $this->assertSame('name is required', $result->firstError());
    }

    public function test_validation_result_first_error_returns_null_on_success(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
        ]);

        $result = $schema->validateData(['name' => 'Alice']);

        $this->assertNull($result->firstError());
    }
}
