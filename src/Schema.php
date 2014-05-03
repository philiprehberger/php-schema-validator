<?php

declare(strict_types=1);

namespace PhilipRehberger\SchemaValidator;

use PhilipRehberger\SchemaValidator\Contracts\SchemaType;
use PhilipRehberger\SchemaValidator\Types\AnySchema;
use PhilipRehberger\SchemaValidator\Types\ArraySchema;
use PhilipRehberger\SchemaValidator\Types\BoolSchema;
use PhilipRehberger\SchemaValidator\Types\EnumSchema;
use PhilipRehberger\SchemaValidator\Types\FloatSchema;
use PhilipRehberger\SchemaValidator\Types\IntSchema;
use PhilipRehberger\SchemaValidator\Types\ObjectSchema;
use PhilipRehberger\SchemaValidator\Types\StringSchema;

/**
 * Static factory for building schema validators.
 */
final class Schema
{
    /**
     * Create an object schema with the given field definitions.
     *
     * @param  array<string, SchemaType>  $fields
     */
    public static function object(array $fields): ObjectSchema
    {
        return new ObjectSchema($fields);
    }

    /**
     * Create a string schema.
     */
    public static function string(): StringSchema
    {
        return new StringSchema;
    }

    /**
     * Create an integer schema.
     */
    public static function int(): IntSchema
    {
        return new IntSchema;
    }

    /**
     * Create a float schema.
     */
    public static function float(): FloatSchema
    {
        return new FloatSchema;
    }

    /**
     * Create a boolean schema.
     */
    public static function bool(): BoolSchema
    {
        return new BoolSchema;
    }

    /**
     * Create an array schema with typed items.
     */
    public static function arrayOf(SchemaType $itemSchema): ArraySchema
    {
        return new ArraySchema($itemSchema);
    }

    /**
     * Create an enum schema with the allowed values.
     *
     * @param  array<mixed>  $values
     */
    public static function enum(array $values): EnumSchema
    {
        return new EnumSchema($values);
    }

    /**
     * Create a schema that accepts any value.
     */
    public static function any(): AnySchema
    {
        return new AnySchema;
    }
}
