<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Tests;

use PhilipRehberger\SchemaValidator\Schema;
use PHPUnit\Framework\TestCase;

class TypeSchemaTest extends TestCase
{
    // ---------------------------------------------------------------
    // IntSchema edge cases
    // ---------------------------------------------------------------

    public function test_int_rejects_null_by_default(): void
    {
        $errors = Schema::int()->validate(null, 'age');

        $this->assertContains('age must not be null', $errors);
    }

    public function test_int_rejects_float(): void
    {
        $errors = Schema::int()->validate(3.14, 'count');

        $this->assertContains('count must be an integer', $errors);
    }

    public function test_int_rejects_bool(): void
    {
        $errors = Schema::int()->validate(true, 'count');

        $this->assertNotEmpty($errors);
    }

    public function test_int_accepts_zero(): void
    {
        $errors = Schema::int()->validate(0, 'count');

        $this->assertEmpty($errors);
    }

    public function test_int_accepts_negative(): void
    {
        $errors = Schema::int()->validate(-10, 'offset');

        $this->assertEmpty($errors);
    }

    public function test_int_min_boundary(): void
    {
        $schema = Schema::int()->min(5);

        $this->assertNotEmpty($schema->validate(4, 'n'));
        $this->assertEmpty($schema->validate(5, 'n'));
        $this->assertEmpty($schema->validate(6, 'n'));
    }

    public function test_int_max_boundary(): void
    {
        $schema = Schema::int()->max(10);

        $this->assertEmpty($schema->validate(9, 'n'));
        $this->assertEmpty($schema->validate(10, 'n'));
        $this->assertNotEmpty($schema->validate(11, 'n'));
    }

    public function test_int_uses_default_path_prefix(): void
    {
        $errors = Schema::int()->validate('bad');

        $this->assertContains('value must be an integer', $errors);
    }

    // ---------------------------------------------------------------
    // FloatSchema edge cases
    // ---------------------------------------------------------------

    public function test_float_rejects_null_by_default(): void
    {
        $errors = Schema::float()->validate(null, 'price');

        $this->assertContains('price must not be null', $errors);
    }

    public function test_float_rejects_string(): void
    {
        $errors = Schema::float()->validate('3.14', 'price');

        $this->assertContains('price must be a float', $errors);
    }

    public function test_float_rejects_bool(): void
    {
        $errors = Schema::float()->validate(true, 'price');

        $this->assertNotEmpty($errors);
    }

    public function test_float_accepts_integer(): void
    {
        $errors = Schema::float()->validate(5, 'price');

        $this->assertEmpty($errors);
    }

    public function test_float_accepts_zero(): void
    {
        $errors = Schema::float()->validate(0.0, 'price');

        $this->assertEmpty($errors);
    }

    public function test_float_accepts_negative(): void
    {
        $errors = Schema::float()->validate(-99.9, 'balance');

        $this->assertEmpty($errors);
    }

    public function test_float_min_boundary(): void
    {
        $schema = Schema::float()->min(1.5);

        $this->assertNotEmpty($schema->validate(1.4, 'n'));
        $this->assertEmpty($schema->validate(1.5, 'n'));
        $this->assertEmpty($schema->validate(1.6, 'n'));
    }

    public function test_float_max_boundary(): void
    {
        $schema = Schema::float()->max(9.9);

        $this->assertEmpty($schema->validate(9.8, 'n'));
        $this->assertEmpty($schema->validate(9.9, 'n'));
        $this->assertNotEmpty($schema->validate(10.0, 'n'));
    }

    public function test_float_uses_default_path_prefix(): void
    {
        $errors = Schema::float()->validate('bad');

        $this->assertContains('value must be a float', $errors);
    }

    // ---------------------------------------------------------------
    // BoolSchema edge cases
    // ---------------------------------------------------------------

    public function test_bool_rejects_null_by_default(): void
    {
        $errors = Schema::bool()->validate(null, 'active');

        $this->assertContains('active must not be null', $errors);
    }

    public function test_bool_rejects_integer(): void
    {
        $errors = Schema::bool()->validate(0, 'flag');

        $this->assertContains('flag must be a boolean', $errors);
    }

    public function test_bool_rejects_string(): void
    {
        $errors = Schema::bool()->validate('false', 'flag');

        $this->assertContains('flag must be a boolean', $errors);
    }

    public function test_bool_uses_default_path_prefix(): void
    {
        $errors = Schema::bool()->validate('bad');

        $this->assertContains('value must be a boolean', $errors);
    }

    // ---------------------------------------------------------------
    // EnumSchema — string values
    // ---------------------------------------------------------------

    public function test_enum_with_string_values_accepts_valid(): void
    {
        $schema = Schema::enum(['a', 'b', 'c']);

        $this->assertEmpty($schema->validate('a', 'letter'));
        $this->assertEmpty($schema->validate('c', 'letter'));
    }

    public function test_enum_with_string_values_rejects_invalid(): void
    {
        $schema = Schema::enum(['a', 'b', 'c']);
        $errors = $schema->validate('d', 'letter');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('"a"', $errors[0]);
        $this->assertStringContainsString('"b"', $errors[0]);
        $this->assertStringContainsString('"c"', $errors[0]);
    }

    // ---------------------------------------------------------------
    // EnumSchema — integer values
    // ---------------------------------------------------------------

    public function test_enum_with_integer_values_accepts_valid(): void
    {
        $schema = Schema::enum([1, 2, 3]);

        $this->assertEmpty($schema->validate(1, 'level'));
        $this->assertEmpty($schema->validate(3, 'level'));
    }

    public function test_enum_with_integer_values_rejects_invalid(): void
    {
        $schema = Schema::enum([1, 2, 3]);
        $errors = $schema->validate(4, 'level');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('1', $errors[0]);
    }

    public function test_enum_integer_uses_strict_comparison(): void
    {
        $schema = Schema::enum([1, 2, 3]);
        $errors = $schema->validate('1', 'level');

        $this->assertNotEmpty($errors);
    }

    // ---------------------------------------------------------------
    // EnumSchema — mixed value types
    // ---------------------------------------------------------------

    public function test_enum_with_mixed_types(): void
    {
        $schema = Schema::enum([1, 'two', true, 3.14]);

        $this->assertEmpty($schema->validate(1, 'val'));
        $this->assertEmpty($schema->validate('two', 'val'));
        $this->assertEmpty($schema->validate(true, 'val'));
        $this->assertEmpty($schema->validate(3.14, 'val'));
        $this->assertNotEmpty($schema->validate(false, 'val'));
        $this->assertNotEmpty($schema->validate(2, 'val'));
    }

    // ---------------------------------------------------------------
    // EnumSchema — boolean values
    // ---------------------------------------------------------------

    public function test_enum_with_boolean_values(): void
    {
        $schema = Schema::enum([true, false]);

        $this->assertEmpty($schema->validate(true, 'flag'));
        $this->assertEmpty($schema->validate(false, 'flag'));
        $this->assertNotEmpty($schema->validate(1, 'flag'));
        $this->assertNotEmpty($schema->validate(0, 'flag'));
    }

    // ---------------------------------------------------------------
    // EnumSchema — null handling
    // ---------------------------------------------------------------

    public function test_enum_rejects_null_by_default(): void
    {
        $errors = Schema::enum(['a', 'b'])->validate(null, 'status');

        $this->assertContains('status must not be null', $errors);
    }

    public function test_enum_uses_default_path_prefix(): void
    {
        $errors = Schema::enum(['a'])->validate('z');

        $this->assertStringContainsString('value must be one of', $errors[0]);
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — string
    // ---------------------------------------------------------------

    public function test_string_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'bio' => Schema::string()->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_string_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'bio' => Schema::string()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['bio' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_string_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'bio' => Schema::string()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['bio' => 'Hello']);
        $this->assertTrue($result->passes());
    }

    public function test_string_nullable_optional_invalid_value(): void
    {
        $schema = Schema::object([
            'bio' => Schema::string()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['bio' => 123]);
        $this->assertTrue($result->fails());
        $this->assertContains('bio must be a string', $result->errors());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — int
    // ---------------------------------------------------------------

    public function test_int_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'age' => Schema::int()->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_int_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'age' => Schema::int()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['age' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_int_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'age' => Schema::int()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['age' => 25]);
        $this->assertTrue($result->passes());
    }

    public function test_int_nullable_optional_invalid_value(): void
    {
        $schema = Schema::object([
            'age' => Schema::int()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['age' => 'twenty']);
        $this->assertTrue($result->fails());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — float
    // ---------------------------------------------------------------

    public function test_float_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'score' => Schema::float()->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_float_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'score' => Schema::float()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['score' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_float_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'score' => Schema::float()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['score' => 9.5]);
        $this->assertTrue($result->passes());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — bool
    // ---------------------------------------------------------------

    public function test_bool_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'verified' => Schema::bool()->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_bool_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'verified' => Schema::bool()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['verified' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_bool_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'verified' => Schema::bool()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['verified' => true]);
        $this->assertTrue($result->passes());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — enum
    // ---------------------------------------------------------------

    public function test_enum_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'role' => Schema::enum(['admin', 'user'])->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_enum_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'role' => Schema::enum(['admin', 'user'])->nullable()->optional(),
        ]);

        $result = $schema->validateData(['role' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_enum_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'role' => Schema::enum(['admin', 'user'])->nullable()->optional(),
        ]);

        $result = $schema->validateData(['role' => 'admin']);
        $this->assertTrue($result->passes());
    }

    public function test_enum_nullable_optional_invalid_value(): void
    {
        $schema = Schema::object([
            'role' => Schema::enum(['admin', 'user'])->nullable()->optional(),
        ]);

        $result = $schema->validateData(['role' => 'superadmin']);
        $this->assertTrue($result->fails());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — array
    // ---------------------------------------------------------------

    public function test_array_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'tags' => Schema::arrayOf(Schema::string())->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_array_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'tags' => Schema::arrayOf(Schema::string())->nullable()->optional(),
        ]);

        $result = $schema->validateData(['tags' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_array_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'tags' => Schema::arrayOf(Schema::string())->nullable()->optional(),
        ]);

        $result = $schema->validateData(['tags' => ['php', 'test']]);
        $this->assertTrue($result->passes());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — any
    // ---------------------------------------------------------------

    public function test_any_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'meta' => Schema::any()->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_any_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'meta' => Schema::any()->nullable()->optional(),
        ]);

        $result = $schema->validateData(['meta' => null]);
        $this->assertTrue($result->passes());
    }

    // ---------------------------------------------------------------
    // nullable() + optional() combined — object
    // ---------------------------------------------------------------

    public function test_object_nullable_optional_missing_field(): void
    {
        $schema = Schema::object([
            'address' => Schema::object([
                'city' => Schema::string(),
            ])->nullable()->optional(),
        ]);

        $result = $schema->validateData([]);
        $this->assertTrue($result->passes());
    }

    public function test_object_nullable_optional_null_value(): void
    {
        $schema = Schema::object([
            'address' => Schema::object([
                'city' => Schema::string(),
            ])->nullable()->optional(),
        ]);

        $result = $schema->validateData(['address' => null]);
        $this->assertTrue($result->passes());
    }

    public function test_object_nullable_optional_valid_value(): void
    {
        $schema = Schema::object([
            'address' => Schema::object([
                'city' => Schema::string(),
            ])->nullable()->optional(),
        ]);

        $result = $schema->validateData(['address' => ['city' => 'Vienna']]);
        $this->assertTrue($result->passes());
    }

    public function test_object_nullable_optional_invalid_value(): void
    {
        $schema = Schema::object([
            'address' => Schema::object([
                'city' => Schema::string(),
            ])->nullable()->optional(),
        ]);

        $result = $schema->validateData(['address' => ['city' => 123]]);
        $this->assertTrue($result->fails());
    }
}
