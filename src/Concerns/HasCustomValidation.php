<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Concerns;

/**
 * Provides custom validation and value transformation support.
 */
trait HasCustomValidation
{
    /** @var callable(mixed): ?string|null */
    private $customValidator = null;

    /** @var callable(mixed): mixed|null */
    private $transformer = null;

    /**
     * Set a custom validation callback.
     *
     * The callable receives the value and should return null if valid,
     * or a string error message if invalid.
     *
     * @param  callable(mixed): ?string  $validator
     */
    public function custom(callable $validator): static
    {
        $this->customValidator = $validator;

        return $this;
    }

    /**
     * Set a value transformer that runs before validation.
     *
     * The callable receives the value and should return the transformed value.
     *
     * @param  callable(mixed): mixed  $transformer
     */
    public function transform(callable $transformer): static
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Apply the transformer to a value if one is set.
     */
    protected function applyTransform(mixed $value): mixed
    {
        if ($this->transformer !== null) {
            return ($this->transformer)($value);
        }

        return $value;
    }

    /**
     * Run the custom validator and return any error.
     *
     * @return array<string>
     */
    protected function runCustomValidator(mixed $value, string $prefix): array
    {
        if ($this->customValidator !== null) {
            $error = ($this->customValidator)($value);

            if ($error !== null) {
                return ["{$prefix} {$error}"];
            }
        }

        return [];
    }
}
