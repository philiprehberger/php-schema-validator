<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Types;

use PhilipRehberger\SchemaValidator\Contracts\SchemaType;

/**
 * Schema validator for integer values.
 */
class IntSchema implements SchemaType
{
    private ?int $min = null;

    private ?int $max = null;

    private bool $isOptional = false;

    private bool $isNullable = false;

    /**
     * Set the minimum allowed value.
     */
    public function min(int $value): static
    {
        $this->min = $value;

        return $this;
    }

    /**
     * Set the maximum allowed value.
     */
    public function max(int $value): static
    {
        $this->max = $value;

        return $this;
    }

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

            $errors[] = "{$prefix} must not be null";

            return $errors;
        }

        if (! is_int($value)) {
            $errors[] = "{$prefix} must be an integer";

            return $errors;
        }

        if ($this->min !== null && $value < $this->min) {
            $errors[] = "{$prefix} must be at least {$this->min}";
        }

        if ($this->max !== null && $value > $this->max) {
            $errors[] = "{$prefix} must be at most {$this->max}";
        }

        return $errors;
    }
}
