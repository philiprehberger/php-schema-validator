<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Types;

use PhilipRehberger\SchemaValidator\Concerns\HasCustomValidation;
use PhilipRehberger\SchemaValidator\Contracts\SchemaType;
use PhilipRehberger\SchemaValidator\ValidationResult;

/**
 * Schema validator for objects (associative arrays) with named fields.
 */
class ObjectSchema implements SchemaType
{
    use HasCustomValidation;

    private bool $isOptional = false;

    private bool $isNullable = false;

    /** @var array<callable(array<string, mixed>): ?string> */
    private array $crossFieldValidators = [];

    /** @var array<array{field: string, value: mixed, schema: array<string, SchemaType>}> */
    private array $conditionalRules = [];

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
     * Add a cross-field validation callback.
     *
     * The callable receives the full data array and should return null if valid,
     * or a string error message if invalid.
     *
     * @param  callable(array<string, mixed>): ?string  $validator
     */
    public function crossField(callable $validator): static
    {
        $this->crossFieldValidators[] = $validator;

        return $this;
    }

    /**
     * Conditionally validate additional fields when a field matches a given value.
     *
     * @param  array<string, SchemaType>  $thenSchema
     */
    public function when(string $field, mixed $value, array $thenSchema): static
    {
        $this->conditionalRules[] = [
            'field' => $field,
            'value' => $value,
            'schema' => $thenSchema,
        ];

        return $this;
    }

    /**
     * Create a new ObjectSchema combining fields from this schema with additional fields.
     *
     * @param  array<string, SchemaType>  $additionalFields
     */
    public function extend(array $additionalFields): static
    {
        $merged = array_merge($this->fields, $additionalFields);

        $new = new static($merged);

        // Copy over cross-field validators and conditional rules
        $new->crossFieldValidators = $this->crossFieldValidators;
        $new->conditionalRules = $this->conditionalRules;
        $new->isOptional = $this->isOptional;
        $new->isNullable = $this->isNullable;

        return $new;
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

        $value = $this->applyTransform($value);

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

        foreach ($this->conditionalRules as $rule) {
            if (array_key_exists($rule['field'], $value) && $value[$rule['field']] === $rule['value']) {
                foreach ($rule['schema'] as $field => $schema) {
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
            }
        }

        if ($errors === []) {
            foreach ($this->crossFieldValidators as $validator) {
                $error = $validator($value);

                if ($error !== null) {
                    $errors[] = $error;
                }
            }

            $errors = [...$errors, ...$this->runCustomValidator($value, $prefix)];
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
