<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Types;

use PhilipRehberger\SchemaValidator\Contracts\SchemaType;
use PhilipRehberger\SchemaValidator\ValidationResult;

/**
 * Schema validator for objects (associative arrays) with named fields.
 */
class ObjectSchema implements SchemaType
{
    private bool $isOptional = false;

    private bool $isNullable = false;

    /**
     * Create a new object schema.
     *
     * @param  array<string, SchemaType>  $fields
     */
    public function __construct(
        private readonly array $fields,
    ) {}

    /**
     * Mark the field as optional (may be missing from the parent object).
     */
    public function optional(): static
    {
        $this->isOptional = true;

        return $this;
    }

    /**
     * Allow null as a valid value.
     */
    public function nullable(): static
    {
        $this->isNullable = true;

        return $this;
    }

    /**
     * Check whether this field is optional.
     */
    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    /**
     * Validate the given value and return an array of error messages.
     *
     * @return array<string>
     */
    public function validate(mixed $value, string $path = ''): array
    {
        $prefix = $path !== '' ? $path : 'value';
        $errors = [];

        if ($value === null) {
            if ($this->isNullable) {
                return [];
            }

            return ["{$prefix} must not be null"];
        }

        if (! is_array($value)) {
            return ["{$prefix} must be an object"];
        }

        foreach ($this->fields as $field => $schema) {
            $fieldPath = $path !== '' ? "{$path}.{$field}" : $field;
            $isOptionalField = method_exists($schema, 'isOptional') && $schema->isOptional();

            if (! array_key_exists($field, $value)) {
                if (! $isOptionalField) {
                    $errors[] = "{$fieldPath} is required";
                }

                continue;
            }

            $fieldErrors = $schema->validate($value[$field], $fieldPath);
            $errors = [...$errors, ...$fieldErrors];
        }

        return $errors;
    }

    /**
     * Validate the given data and return a ValidationResult.
     *
     * @param  array<string, mixed>  $data
     */
    public function validateData(array $data): ValidationResult
    {
        $errors = $this->validate($data);

        return new ValidationResult($errors);
    }
}
