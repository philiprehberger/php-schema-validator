<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Types;

use PhilipRehberger\SchemaValidator\Concerns\HasCustomValidation;
use PhilipRehberger\SchemaValidator\Contracts\SchemaType;

/**
 * Schema validator for string values.
 */
class StringSchema implements SchemaType
{
    use HasCustomValidation;

    private ?int $min = null;

    private ?int $max = null;

    private bool $isEmail = false;

    private bool $isUrl = false;

    private bool $isUuid = false;

    private ?string $regex = null;

    private bool $isOptional = false;

    private bool $isNullable = false;

    /**
     * Set the minimum string length.
     */
    public function min(int $length): static
    {
        $this->min = $length;

        return $this;
    }

    /**
     * Set the maximum string length.
     */
    public function max(int $length): static
    {
        $this->max = $length;

        return $this;
    }

    /**
     * Require the value to be a valid email address.
     */
    public function email(): static
    {
        $this->isEmail = true;

        return $this;
    }

    /**
     * Require the value to be a valid URL.
     */
    public function url(): static
    {
        $this->isUrl = true;

        return $this;
    }

    /**
     * Require the value to be a valid UUID.
     */
    public function uuid(): static
    {
        $this->isUuid = true;

        return $this;
    }

    /**
     * Require the value to match the given regular expression.
     */
    public function regex(string $pattern): static
    {
        $this->regex = $pattern;

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

        $value = $this->applyTransform($value);

        if (! is_string($value)) {
            $errors[] = "{$prefix} must be a string";

            return $errors;
        }

        if ($this->min !== null && mb_strlen($value) < $this->min) {
            $errors[] = "{$prefix} must be at least {$this->min} characters";
        }

        if ($this->max !== null && mb_strlen($value) > $this->max) {
            $errors[] = "{$prefix} must be at most {$this->max} characters";
        }

        if ($this->isEmail && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = "{$prefix} must be a valid email address";
        }

        if ($this->isUrl && filter_var($value, FILTER_VALIDATE_URL) === false) {
            $errors[] = "{$prefix} must be a valid URL";
        }

        if ($this->isUuid && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) !== 1) {
            $errors[] = "{$prefix} must be a valid UUID";
        }

        if ($this->regex !== null && preg_match($this->regex, $value) !== 1) {
            $errors[] = "{$prefix} must match the pattern {$this->regex}";
        }

        if ($errors === []) {
            $errors = [...$errors, ...$this->runCustomValidator($value, $prefix)];
        }

        return $errors;
    }
}
