<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator;

/**
 * Immutable container for validation results.
 */
final readonly class ValidationResult
{
    /**
     * Create a new validation result.
     *
     * @param  array<string>  $errors
     */
    public function __construct(
        private array $errors = [],
    ) {}

    /**
     * Check if validation passed with no errors.
     */
    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * Check if validation failed with one or more errors.
     */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Get all validation error messages.
     *
     * @return array<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error message, or null if no errors.
     */
    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
