<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator\Contracts;

/**
 * Contract for all schema type validators.
 */
interface SchemaType
{
    /**
     * Validate the given value and return an array of error messages.
     *
     * @return array<string>
     */
    public function validate(mixed $value, string $path = ''): array;
}
