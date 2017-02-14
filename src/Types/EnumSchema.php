<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Types;

use PhilipRehberger\SchemaValidator\Concerns\HasCustomValidation;
use PhilipRehberger\SchemaValidator\Contracts\SchemaType;

/**
 * Schema validator for values that must be one of a set of allowed values.
 */
class EnumSchema implements SchemaType
{
    use HasCustomValidation;

    private bool $isOptional = false;

    private bool $isNullable = false;

    /**
     * Create a new enum schema.
     *
     * @param  array<mixed>  $values
     */
    public function __construct(
        private readonly array $values,
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

        if ($value === null) {
            if ($this->isNullable) {
                return [];
            }

            return ["{$prefix} must not be null"];
        }

        $value = $this->applyTransform($value);

        if (! in_array($value, $this->values, true)) {
            $allowed = implode(', ', array_map(
                static fn (mixed $v): string => is_string($v) ? "\"{$v}\"" : (string) $v,
                $this->values,
            ));

            return ["{$prefix} must be one of [{$allowed}]"];
        }

        return $this->runCustomValidator($value, $prefix);
    }
}
