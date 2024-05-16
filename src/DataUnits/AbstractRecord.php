<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

use RuntimeException;

/**
 * Base class for all records.
 *
 * Implements the following features:
 * - Attribute list.
 * - Attribute specifications.
 * - Retrieve record attribute values (including nested records) as an associative array (see `internalGetAttributes()`).
 * - Magic `__get()` method to retrieve values of protected or private attributes.
 * - Magic `__set()` method to disallow dynamic properties.
 */
abstract class AbstractRecord
{
    /**
     * Flag for using object cloning in `internalGetAttributes()`. If it is set, attributes that are
     * objects will be cloned on getting.
     */
    final public const GETTER_CLONE_OBJECTS = 1 << 0;

    /**
     * Default options for function retriving attribute valeues `internalGetAttributes()`.
     * You can override this constant in a child class to change the default behavior of
     * `internalGetAttributes()`.
     */
    public const GETTER_DEFAULT_OPTIONS = 0;

    /**
     */
    protected function __construct()
    {
    }

    /**
     * Returns a list of record attribute specifications.
     *
     * Note, if the `attributes()` method is not overridden, all attributes must be listed here.
     *
     * Usually the return value is an associative array (attribute => [specifications]):
     *
     * ```php
     *     [
     *         'attribute1' => ['specification1' => '...', 'specification2' => '...', ...],
     *         'attribute2' => ['specification1' => '...', 'specification2' => '...', ...],
     *         'attribute3' => [],
     *         'attribute4',
     *         ...
     *     ]
     * ```
     * Record attribute specification kinds will be defined in child classes.
     *
     * Empty attribute specifications (empty specification arrays - []) may be omitted.
     *
     * Note, in order to inherit attribute specifications defined in the parent class, a child class
     * needs to merge the parent attribute specifications with child attribute specifications using
     * functions such as `array_merge()` or `array_merge_recursive()`.
     *
     * @return array<int|string,string|array<string,mixed>> a list of record attribute specifications
     * (attribute => [specifications]).
     */
    protected static function attributeSpecifications(): array
    {
        return [];
    }

    /**
     * Extracts attribute specification values from `attributeSpecifications()`.
     *
     * @param string $specificationName name of the specification to be extracted.
     * @param bool replaceMode specifies whether to replace (if `true`) or add (if `false`) duplicate
     * specification values.
     *
     * @return array<string,mixed> extracted attribute specification values
     * (attribute => specificationValue).
     */
    protected static function extractAttributeSpecificationValues(string $specificationName, bool $replaceMode): array
    {
        $extractedSpecValues = [];

        foreach (static::attributeSpecifications() as $attribute => $specifications) {
            if (is_array($specifications)) {
                foreach ($specifications as $specification => $specificationValue) {
                    if ($specification === $specificationName) {
                        $extractedSpecValues[$attribute] = match ($replaceMode) {
                            true => $specificationValue,
                            default => array_merge(
                                $extractedSpecValues[$attribute] ?? [],
                                is_array($specificationValue) ? $specificationValue : [$specificationValue]
                            ),
                        };
                    }
                }
            }
        }

        return $extractedSpecValues;
    }

    /**
     * Returns the extracted list of record attribute names.
     *
     * Extracts attribute names from `attributeSpecifications()`.
     *
     * @return array<int,string> a list of record attribute names.
     */
    protected static function extractAttributeList(): array
    {
        $result = [];
        foreach (static::attributeSpecifications() as $key => $value) {
            if (is_string($key)) {
                $result[] = $key;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Returns a full list of record attribute names.
     *
     * Note, it is recommended to use `attributeList()` to retrieve verified attribute names.
     *
     * By default, unless you override this method in a child class, all attributes listed in
     * `attributeSpecifications()` are extracted using `extractAttributeList()`. This behavior
     * can be changed in child classes by overriding the `extractAttributeList()` method.
     *
     * When you override this function, the return value must be an array of strings:
     *
     * ```php
     *     ['attribute1', 'attribute2', ...]
     * ```
     * Note, in order to inherit attributes defined in the parent class, a child class needs to
     * merge the parent attributes with child attributes using functions such as `array_merge()`.
     *
     * @return array<int,string> a list of record attribute names.
     *
     * @see attributeSpecifications()
     * @see extractAttributeList()
     * @see attributeList()
     */
    protected static function attributes(): array
    {
        /**
         * A shared cache of attribute name lists of all child classes (for php 8.1 and later):
         * (className => [attributeNames]).
         *
         * @var array<string,array<int,string>>
         */
        static $attributes = [];

        if (!isset($attributes[static::class])) {
            $attributes[static::class] = static::extractAttributeList();
        }

        return $attributes[static::class];
    }

    /**
     * Returns a list of verified record attribute names.
     *
     * @return array<int,string> a list of verified record attribute names.
     *
     * @throws RuntimeException if the attribute is invalid.
     *
     * @see attributes()
     */
    final public static function attributeList(): array
    {
        /**
         * A shared cache of verified lists of attribute names of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => [attributeNames]).
         *
         * @var array<string,array<int,string>>
         */
        static $attributeList = [];

        if (!isset($attributeList[static::class])) {
            $attributeList[static::class] = static::attributes();
            // Attribute names validation.
            foreach ($attributeList[static::class] as $attribute) {
                if (!is_string($attribute)) {
                    throw new RuntimeException(
                        'Invalid attribute \'' . var_export($attribute, true) . '\' at '
                            . static::class . '::attributes().'
                    );
                } elseif (!property_exists(static::class, $attribute)) {
                    throw new RuntimeException(
                        "Invalid attribute '$attribute' at "
                            . static::class . '::attributes(). Property does not exist.'
                    );
                }
            }
        }

        return $attributeList[static::class];
    }

    /**
     * Returns a list of valid attribute names.
     *
     * Only attribute names listed at `attributeList()` are returned.
     *
     * @param array<int,string>|null $attributes list of attribute names to check.
     * Defaults to null, meaning all attribute names listed in `attributeList()` will be returned.
     *
     * @return array<int,string> a list of valid attribute names.
     */
    protected static function normalizeAttributeList(array|null $attributes): array
    {
        return (null === $attributes)
            ? static::attributeList()
            : array_intersect(static::attributeList(), $attributes);
    }

    /**
     * Returns the value of the attribute.
     *
     * The return value depends on the type of the attribute (this is the default behavior that applies
     * when other options are not set):
     * - If the attribute itself is an instance of the `AbstractRecord` class, it will be returned
     * using its own `internalGetAttribute()` function.
     *
     * Supported options for getting attributes:
     * - GETTER_CLONE_OBJECTS - whether to clone an attribute that is an object on getting.
     *
     * @param string $attribute a attribute name.
     * @param int $options bitmask of attribute getter options.
     *
     * @return mixed attribute value.
     */
    protected function getAttribute(string $attribute, int $options): mixed
    {
        return match (true) {

            ($this->$attribute instanceof (self::class))
            => self::toSelfClass($this->$attribute)->internalGetAttributes(null, $options),

            (($options & self::GETTER_CLONE_OBJECTS) && is_object($this->$attribute))
            => clone $this->$attribute,

            default => $this->$attribute,
        };
    }

    /**
     * Helper for casting object type to self class type.
     *
     * The object must first be checked for a this class type compliance with
     * ($o instanceof (self::class)).
     *
     * @param object $o object to type cast
     *
     * @return self objet with type cast
     */
    final protected static function toSelfClass(object $o): self
    {
        return $o;
    }

    /**
     * Returns the values of the record attributes in massive way.
     *
     * Incorrect attribute names will be skipped.
     *
     * @param array<int,string>|null $attributes list of attributes whose value needs to be returned.
     * Defaults to null, meaning all attributes listed in `attributeList()` will be returned.
     * If it is an array, only the attributes in the array will be returned.
     * @param int $options bitmask of attribute getter options (see `getAttribute()`).
     *
     * @return array<string,mixed> record attribute values (attribute => value).
     *
     * @see getAttribute()
     */
    protected function internalGetAttributes(array|null $attributes = null, int|null $options = null): array
    {
        if (null === $options) {
            $options = static::GETTER_DEFAULT_OPTIONS;
        }

        $attributes = $this->normalizeAttributeList($attributes);

        $values = [];

        foreach ($attributes as $attribute) {
            $values[$attribute] = $this->getAttribute($attribute, $options);
        }

        return $values;
    }

    /**
     * Verifies whether the attribute is valid.
     *
     * All attributes listed in `attributeList()` are considered valid.
     *
     * @param string $attribute attribute name.
     * @param string|null &$message if attribute is not valid, the error message can be returned here.
     *
     * @return bool whether the attribute is set.
     *
     */
    protected function isAttributeValid(string $attribute, string|null &$message): bool
    {
        $message = null;
        return in_array($attribute, $this->attributeList(), true);
    }

    /**
     * Returns attribute value.
     *
     * Implementation of the `__get()` magic method to retrieve values of protected or private attributes.
     *
     * Only attributes listed in `attributeList()` may be returned.
     *
     * @param string $name attribute name.
     *
     * @return mixed attribute value.
     *
     * @throws RuntimeException if the attribute is invalid.
     */
    public function __get(string $attribute): mixed
    {
        if ($this->isAttributeValid($attribute, $message)) {
            return $this->$attribute;
        } elseif (!is_null($message)) {
            throw new RuntimeException($message);
        } elseif (property_exists($this, $attribute)) {
            throw new RuntimeException('Cannot access private or protected property: ' . $this::class . "::$attribute");
        } else {
            throw new RuntimeException('Undefined property: ' . $this::class . "::$attribute");
        }
        return null;
    }

    /**
     * Disallows dynamic properties.
     *
     * Implementation of the `__set()` magic method to disallow dynamic properties.
     */
    public function __set(string $attribute, mixed $value): void
    {
        if (in_array($attribute, $this->attributeList(), true)) {
            throw new RuntimeException('Direct access to attribute is denied: ' . $this::class . "::$attribute");
        } elseif (property_exists($this, $attribute)) {
            throw new RuntimeException('Cannot access private or protected property: ' . $this::class . "::$attribute");
        } else {
            throw new RuntimeException('Undefined property: ' . $this::class . "::$attribute");
        }
    }

    /**
     * Returns the attribute values in massive way.
     *
     * Incorrect attribute names will be skipped.
     *
     * @param array<int,string>|null $attributes list of attributes whose value needs to be returned.
     * Defaults to null, meaning all attributes will be returned.
     * If it is an array, only the attributes in the array will be returned.
     *
     * @return array<string,mixed> attribute values (attribute => value).
     *
     * @see internalGetAttributes()
     */
    public function getAttributes(array|null $attributes = null): array
    {
        return $this->internalGetAttributes($attributes);
    }
}
