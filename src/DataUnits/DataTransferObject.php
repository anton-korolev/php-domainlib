<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

use ReflectionMethod;
use RuntimeException;

/**
 * Base class for Data Transfer Objects (DTO).
 *
 * Implements the following commonly used features:
 * - Create a DTO from an associative array with attribute type control and automatic creation of all
 * internal DTOs (see `createFromArray()`).
 * - Convert a DTO (including nested DTOs) to an associative array (see `DTOtoArray()`).
 *
 * Supports the following attribute specifications:
 * - `dtoClass` - name of the attribute's DTO class.
 *
 * Typical example of a DTO class (php 8.1):
 * ```php
 * class UserDTO extends DataTransferObject
 * {
 *     public function __construct(
 *         public readonly string $id,
 *         public readonly string $name,
 *         public readonly string $email,
 *         public readonly PhoneDTO $phone,
 *     ) {}
 *
 *     protected static function attributeSpecifications(): array
 *     {
 *         return [
 *             'id',
 *             'name',
 *             'email',
 *             'phone' => ['dtoClass' => 'PhoneDTO'],
 *         ];
 *     }
 * }
 *  ```
 */
abstract class DataTransferObject extends AbstractRecord
{
    /**
     * {@inheritdoc}
     */
    public const GETTER_DEFAULT_OPTIONS = parent::GETTER_DEFAULT_OPTIONS & !self::GETTER_CLONE_OBJECTS;

    /**
     * Data Transfer Object constructior.
     *
     * @see DataTransferObject for a typical example of constructor usage.
     * @see createFromArray()
     */
    public function __construct(mixed ...$values)
    {
    }

    /**
     * Helper for converting a Data Transfer Object to an array.
     *
     * @param array<string,mixed>|DataTransferObject $dto input data
     *
     * @return array<string,mixed> converted array (attribute => value).
     */
    public static function DTOtoArray(array|DataTransferObject $dto): array
    {
        if ($dto instanceof DataTransferObject) {
            /** @var DataTransferObject $dto */
            return $dto->internalGetAttributes(null);
        }
        return $dto;
    }

    /**
     * Creates a new instance of a Data Transfer Object from an array.
     *
     * Incorrect attributes from the `$values` array (not listed in `attributes()`) will be skipped
     * without throwing an exception.
     *
     * If an attribute is itself a DTO, its value can be passed as an array:
     * ```php
     * [
     * ...
     *     'phone' => ['country' => '+7', 'code' => '800', 'number' => '1234567890'],
     * ...
     * ]
     * ```
     * In this case, a DTO for this attribute will be created automatically (see `prepareValues()`).
     *
     * @param array<string,mixed> $values associative array of DTO attribute values (attribute => value).
     *
     * @return static new instance of a Data Transfer Object.
     */
    public static function createFromArray(array $values): static
    {
        $values = array_intersect_key($values, array_flip(static::attributeList()));
        static::prepareValues($values);
        return new static(...$values);
    }

    /**
     * {@inheritdoc}
     *
     * If the attribute specifications are empty, extracts the property names from the `__construct()`
     * parameters.
     *
     * @return array<int,string> a list of record attribute names.
     *
     * @throws RuntimeException if the constructor parameters are invalid.
     */
    protected static function extractAttributeList(): array
    {
        $result = parent::extractAttributeList();
        if (!empty($result)) {
            return $result;
        }

        $result = [];
        $constructor = new ReflectionMethod(static::class, '__construct');
        $parameters = $constructor->getParameters();
        foreach ($parameters as $parameter) {
            if (
                !$parameter->isVariadic()
                && property_exists(static::class, $parameter->name)
            ) {
                $result[] = $parameter->name;
            }
        }

        if (empty($result)) {
            throw new RuntimeException(
                'Invalid parameters in \'' . static::class . '::__construct()\'.'
            );
        }

        return $result;
    }

    /**
     * Returns a list of the Data Transfer Object attribute classes.
     *
     * Note, it is recommended to use `attributeDtoClasses()` to retrieve verified DTO attribute
     * classes.
     *
     * Some attributes may themselves be DTOs. And to automatically create all internal DTOs, you
     * must specify their classes.
     *
     * Note, all internal DTOs must inherited from the `DataTransferObject` class.
     *
     * By default, unless you override this function in a child class, all `dtoClass` specification
     * values will be retrieved from `attributeSpecifications()` and returned.
     *
     * When you override this function, the return value must be an associative array of strings:
     * ```php
     * [
     *     'attribute1' => 'dtoClassName1',
     *     'attribute2' => 'dtoClassName2',
     *     ...
     * ]
     * ```
     * Note, in order to inherit DTO class names defined in the parent class, a child DTO class needs
     * to merge the parent DTO class names with child DTO class names using functions such as
     * `array_merge()`.
     *
     * @return array<string,string> list of DTO attribute class names
     * (attribute => dtoClassName).
     *
     * @see attributeSpecifications()
     * @see attributeDtoClasses()
     */
    protected static function dtoClasses(): array
    {
        /**
         * A shared cache of DTO class names for the attributes of all child classes (for php 8.1 and
         * later), indexed by child class name: (ChildClassName => [attributeName => dtoClass]).
         *
         * @var array<string,array<string,string>>
         */
        static $dtoClasses = [];

        if (!isset($dtoClasses[static::class])) {
            // Attribute DTO classes extraction.
            $dtoClasses[static::class] =
                static::extractAttributeSpecificationValues('dtoClass', true);
        }

        return $dtoClasses[static::class];
    }

    /**
     * Returns a list of verified Data Transfer Object attribute classes.
     *
     * Validates all DTO class names specified in `dtoClasses()`. They must extend the
     * `DataTransferObject` class.
     *
     * @return array<string,DataTransferObject> list of verified DTO attribute class names
     * (attribute => dtoClassName).
     *
     * @throws RuntimeException if an invalid `dtoClass` specification is defined for the attribute.
     *
     * @see dtoClasses()
     */
    final public static function attributeDtoClasses(): array
    {
        /**
         * A shared cache of verified DTO class names for the attributes of all child classes (for php 8.1
         * and later), indexed by child class name (ChildClassName => [attributeName => dtoClass]).
         *
         * @var array<string,array<string,DataTransferObject>>
         */
        static $attributeDtoClasses = [];

        if (!isset($attributeDtoClasses[static::class])) {
            $attributeDtoClasses[static::class] = static::dtoClasses();
            // Attribute DTO classes validation.
            foreach ($attributeDtoClasses[static::class] as $attribute => $dtoClass) {
                if (!is_subclass_of($dtoClass, self::class)) {
                    throw new RuntimeException(
                        'Invalid dotClass at \'' . static::class . "::$attribute' attribute."
                            . ' The attribute must extends ' . self::class . ' class.'
                    );
                }
            }
        }

        return $attributeDtoClasses[static::class];
    }

    /**
     * Prepares the attribute values of the Data Transfer Object.
     *
     * Creates a DTO for the attributes listed in `attributeDtoClasses()`.
     *
     * @param array<string,mixed> &$values associative array of DTO attribute values (attribute => value).
     *
     * @return void
     */
    protected static function prepareValues(array &$values): void
    {
        $attributeDtoClasses = static::attributeDtoClasses();

        foreach ($values as $attribute => &$value) {
            if (
                isset($attributeDtoClasses[$attribute])
                && (null !== $value)
                && !($value instanceof (self::class))
            ) {
                $value = $attributeDtoClasses[$attribute]::createFromArray($value);
            }
        }
        unset($value);
    }
}
