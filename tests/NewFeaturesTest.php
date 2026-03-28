<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Tests;

use PhilipRehberger\SchemaValidator\Schema;
use PHPUnit\Framework\TestCase;

class NewFeaturesTest extends TestCase
{
    // --- Conditional fields ---

    public function test_when_condition_met_validates_additional_fields(): void
    {
        $schema = Schema::object([
            'type' => Schema::string(),
            'email' => Schema::string()->email(),
        ])->when('type', 'business', [
            'company_name' => Schema::string()->min(1),
            'tax_id' => Schema::string(),
        ]);

        $result = $schema->validateData([
            'type' => 'business',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue($result->fails());
        $errors = $result->errors();
        $this->assertContains('company_name is required', $errors);
        $this->assertContains('tax_id is required', $errors);
    }

    public function test_when_condition_met_and_fields_present_passes(): void
    {
        $schema = Schema::object([
            'type' => Schema::string(),
            'email' => Schema::string()->email(),
        ])->when('type', 'business', [
            'company_name' => Schema::string()->min(1),
            'tax_id' => Schema::string(),
        ]);

        $result = $schema->validateData([
            'type' => 'business',
            'email' => 'test@example.com',
            'company_name' => 'Acme Corp',
            'tax_id' => 'DE123456789',
        ]);

        $this->assertTrue($result->passes());
    }

    public function test_when_condition_not_met_ignores_additional_fields(): void
    {
        $schema = Schema::object([
            'type' => Schema::string(),
            'email' => Schema::string()->email(),
        ])->when('type', 'business', [
            'company_name' => Schema::string()->min(1),
            'tax_id' => Schema::string(),
        ]);

        $result = $schema->validateData([
            'type' => 'personal',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue($result->passes());
    }

    public function test_when_condition_validates_field_constraints(): void
    {
        $schema = Schema::object([
            'type' => Schema::string(),
        ])->when('type', 'business', [
            'company_name' => Schema::string()->min(3),
        ]);

        $result = $schema->validateData([
            'type' => 'business',
            'company_name' => 'AB',
        ]);

        $this->assertTrue($result->fails());
        $this->assertStringContainsString('company_name', $result->firstError());
    }

    // --- Schema composition (extend) ---

    public function test_extend_creates_new_schema_with_combined_fields(): void
    {
        $base = Schema::object([
            'name' => Schema::string(),
            'email' => Schema::string()->email(),
        ]);

        $admin = $base->extend([
            'role' => Schema::string(),
            'permissions' => Schema::arrayOf(Schema::string()),
        ]);

        // Admin schema requires all four fields
        $result = $admin->validateData([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertTrue($result->fails());
        $errors = $result->errors();
        $this->assertContains('role is required', $errors);
        $this->assertContains('permissions is required', $errors);
    }

    public function test_extend_does_not_mutate_original_schema(): void
    {
        $base = Schema::object([
            'name' => Schema::string(),
        ]);

        $base->extend([
            'role' => Schema::string(),
        ]);

        // Base should still only require 'name'
        $result = $base->validateData([
            'name' => 'Alice',
        ]);

        $this->assertTrue($result->passes());
    }

    public function test_extended_schema_validates_all_fields(): void
    {
        $base = Schema::object([
            'name' => Schema::string(),
            'email' => Schema::string()->email(),
        ]);

        $admin = $base->extend([
            'role' => Schema::string(),
        ]);

        $result = $admin->validateData([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'role' => 'admin',
        ]);

        $this->assertTrue($result->passes());
    }

    // --- Custom error messages (withMessages) ---

    public function test_with_messages_replaces_default_errors(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
            'email' => Schema::string()->email(),
        ]);

        $result = $schema->validateData([
            'name' => 123,
            'email' => 'invalid',
        ]);

        $result = $result->withMessages([
            'name' => 'Please enter your full name',
            'email' => 'A valid email address is required',
        ]);

        $errors = $result->errors();
        $this->assertContains('Please enter your full name', $errors);
        $this->assertContains('A valid email address is required', $errors);
        $this->assertNotContains('name must be a string', $errors);
    }

    public function test_with_messages_keeps_unmatched_errors(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
            'email' => Schema::string()->email(),
        ]);

        $result = $schema->validateData([
            'name' => 123,
            'email' => 'invalid',
        ]);

        $result = $result->withMessages([
            'name' => 'Please enter your full name',
        ]);

        $errors = $result->errors();
        $this->assertContains('Please enter your full name', $errors);
        $this->assertContains('email must be a valid email address', $errors);
    }

    public function test_with_messages_returns_new_instance(): void
    {
        $schema = Schema::object([
            'name' => Schema::string(),
        ]);

        $original = $schema->validateData(['name' => 123]);
        $modified = $original->withMessages([
            'name' => 'Custom message',
        ]);

        // Original should be unchanged
        $this->assertContains('name must be a string', $original->errors());
        $this->assertNotContains('Custom message', $original->errors());

        // Modified should have custom message
        $this->assertContains('Custom message', $modified->errors());
    }
}
