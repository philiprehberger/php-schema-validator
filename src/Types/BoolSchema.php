<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Types;

use PhilipRehberger\SchemaValidator\Concerns\HasCustomValidation;
use PhilipRehberger\SchemaValidator\Contracts\SchemaType;

/**
 * Schema validator for boolean values.
 */
class BoolSchema implements SchemaType
{
    use HasCustomValidation;

    private bool $isOptional = false;

    private bool $isNullable = false;

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

        if ($value === null) {
            if ($this->isNullable) {
                return [];
            }

            return ["{$prefix} must not be null"];
        }

        $value = $this->applyTransform($value);

        if (! is_bool($value)) {
            return ["{$prefix} must be a boolean"];
        }

        return $this->runCustomValidator($value, $prefix);
    }
}
