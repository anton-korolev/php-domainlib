<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

use DomainLib\Results\OperationResult;
use RuntimeException;

/**
 * Base class for entity states.
 *
 * Additional implements the following features:
 * - When creating a new EntityState from an associative array, the missing attribute values will be
 * filled in from the default values (see `attributeDefaults()`).
 *
 * Supports the following additional attribute specifications:
 * - `default` - default value for an entity attribute. Can be a simple value, callable or the name
 * of a static method of the current class, in the latter cases it will be computed at runtime
 * (see `attributeDefaults()`).
 * - `getter` - getter for an entity attribute (see `attributeGetters()`). Applies only to the massive
 * way of getting attribute values using `internalGetAttributes()`.
 * - `setter` - setter for an entity attribute (see `attributeSetters()`). Applies only to the massive
 * way of setting attribute values using `internalSetAttributes()`.
 * - `generator` - generator for an entity attribute (see `attributeGenerators()`). Applies only to the
 * massive way of setting attribute values using `internalSetAttributes()`. Unlike a setter, the
 * generator will be called anyway, even if no new attribute value is passed in.
 *
 * Supports the following attribute options (see options()):
 * - ATTRIBUTE_OPTION_READONLY - read-only attribute flag.
 * - ATTRIBUTE_OPTION_PRIMARYKEY - primary key attribute flag.
 *
 * Typical excample usage EntityState class (php 8.1):
 * ```php
 * class UserState extends EntityState
 * {
 *     protected const DTO_CLASS = UserStateDTO::class;
 *
 *     protected string $id;
 *     protected string $login;
 *     protected Password $password;
 *     protected FullName $fullName;
 *     protected PhoneWithDTO|null $phone;
 *     protected string|null $email;
 *     protected bool $active;
 *     protected DateTimeImmutable $createdAt;
 *     protected DateTimeImmutable $updatedAt;
 *
 *     protected static function attributeSpecifications(): array
 *     {
 *         return [
 *             'id' => [
 *                 'validators' => ['isString', 'trim', 'notEmpty', 'validateId' => static::validateId(...)],
 *             ],
 *
 *             'login' => [
 *                 'validators' => ['isString', 'trim', 'notEmpty', 'validateLogin' => static::validateLogin(...)],
 *             ],
 *
 *             'password' => [
 *                 'class' => Password::class,
 *                 'default' => [],
 *                 'validators' => ['notNull'],
 *             ],
 *
 *             'fullName' => [
 *                 'class' => FullName::class,
 *                 'default' => [],
 *                 'validators' => ['notNull'],
 *             ],
 *
 *             'phone' => [
 *                 'class' => PhoneWithDTO::class,
 *             ],
 *
 *             'email' => [
 *                 'validators' => ['nullableString', 'trim', 'emptyToNull', 'nullableEmail'],
 *             ],
 *
 *             'active' => [
 *                 'validators' => ['isBool'],
 *             ],
 *
 *             'createdAt' => [
 *                 'default' => fn (): int => time(),
 *                 // 'default' => fn (): DateTimeImmutable => new DateTimeImmutable(),
 *                 'validators' => ['dateTimeImmutable'],
 *                 'getter' => static::getTimestump(...),
 *             ],
 *
 *             'updatedAt' => [
 *                 // 'default' => fn (): DateTimeImmutable => new DateTimeImmutable(),
 *                 'generator' => fn (mixed $newValue): mixed => $newValue ?? time(),
 *                 'validators' => ['dateTimeImmutable'],
 *                 'getter' => static::getTimestump(...),
 *             ],
 *         ];
 *     }
 *
 *     // Factory method to create an instance of `UserState`.
 *     public static function create(
 *         string|null $id,
 *         string $login,
 *         Password $password,
 *         FullName $fullName,
 *         PhoneWithDTO|null $phone,
 *         string|null $email,
 *         bool $active,
 *         string $recordPath,
 *         OperationResult $result,
 *     ): static|null {
 *         return parent::internalCreate([
 *             'id' => $id,
 *             'login' => $login,
 *             'password' => $password,
 *             'fullName' => $fullName,
 *             'phone' => $phone,
 *             'email' => $email,
 *             'active' => $active,
 *         ], $recordPath, $result);
 *     }
 *
 *     // Additional Id validator.
 *     protected static function validateId(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
 *     {
 *         return true;
 *     }
 *
 *     // Additional Login validator.
 *     protected static function validateLogin(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
 *     {
 *         return true;
 *     }
 *
 *     // Getter. Returns timestamp from the `DateTimeImmutable`.
 *     protected static function getTimestump(string $attribute, DateTimeImmutable $value, int $options): int
 *     {
 *         return $value->getTimestamp();
 *     }
 * }
 * ```
 */
abstract class EntityState extends ValidRecord
{
    /**
     * Read-only attribute flag (see `options()`).
     *
     * Read-only attributes can only be set once (as long as their value is `null`).
     *
     * @var int
     * @see excludeReadonlyAttributes()
     */
    final protected const ATTRIBUTE_OPTION_READONLY = 1 << 0;

    /**
     * Primary key attribute flag (see `options()`).
     *
     * The primary key uniquely identifies an entity. It can consist of one or more attributes.
     *
     * Note, it is recommended to use `ATTRIBUTE_OPTION_PK_MASK` to define primary key attributes.
     *
     * @var int
     * @see primaryKeyAttributes()
     */
    final protected const ATTRIBUTE_OPTION_PRIMARYKEY = 1 << 1;

    /**
     * Primary key attribute mask.
     *
     * By default, the primary key attributes are read-only (see `ATTRIBUTE_OPTION_READONLY`).
     *
     * @var int
     */
    protected const ATTRIBUTE_OPTION_PK_MASK = self::ATTRIBUTE_OPTION_PRIMARYKEY
        | self::ATTRIBUTE_OPTION_READONLY;

    /**
     * Internal factory method to create an instance of `EntityState` from an associative array
     * (attribute => value).
     *
     * Missing attributes will be filled in from the default values (see `attributeDefaults()`).
     *
     * {@inheritdoc}
     *
     * @return static|null a new instance of `EntityState` if the state creation passed without errors,
     * or `null` otherwise.
     *
     * @see attributeDefaults()
     */
    protected static function internalCreate(array $values, string $recordPath, OperationResult $result): static|null
    {
        $values = array_merge(static::attributeDefaults(), $values);
        return parent::internalCreate($values, $recordPath, $result);
    }

    /**
     * Returns a list of entity attribute options.
     *
     * `Options` is a bitmask of attribute properties that are defined by `ATTRIBUTE_OPTION_*` constants.
     *
     * Note, it is recommended to use `attributeOptions()` to retrieve verified attribute options.
     *
     * By default, unless you override this function in a child class, all `options` specification values
     * will be retrieved from `attributeSpecifications()` and returned.
     *
     * When you override this function, the return value must be an associative array of integer:
     * ```php
     * [
     *     'attribute1' => options1,
     *     'attribute2' => options2,
     *     ...
     * ]
     * ```
     *
     * Note, in order to inherit attribute options defined in the parent class, a child class needs to
     * merge the parent attribute options with child attribute options using functions such as `array_merge()`.
     *
     * @return array<string,int> a list of attribute options (attribute => options).
     *
     * @see attributeSpecifications()
     * @see attributeOptions()
     */
    protected static function options(): array
    {
        /**
         * A shared cache of attribute options of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => [attribute => options]).
         *
         * @var array<string,array<string,int>>
         */
        static $options = [];

        if (!isset($options[static::class])) {
            // Attribute options extraction.
            $options[static::class] =
                static::extractAttributeSpecificationValues('options', true);
        }

        return $options[static::class];
    }

    /**
     * Returns a list of verified entity attribute options.
     *
     * Verifies all options specified in `options()`. They must be an `Integer`.
     *
     * @return array<string,int> a list of verified attribute options (attribute => options).
     *
     * @throws RuntimeException if an invalid options is defined for the attribute.
     *
     * @see options()
     */
    final public static function attributeOptions(): array
    {
        /**
         * A shared cache of verified attribute options of all child classes (for php 8.1 and later),
         * indexed by child class name (ChildClassName => [attribute => options]).
         *
         * @var array<string,array<string,int>>
         */
        static $attributeOptions = [];

        if (!isset($attributeOptions[static::class])) {
            $attributeOptions[static::class] = array_merge(
                array_fill_keys(static::attributeList(), 0),
                static::options()
            );
            // Attribute options validation.
            foreach ($attributeOptions[static::class] as $attribute => $option) {
                if (!is_int($option)) {
                    throw new \RuntimeException(
                        'Invalid options at \'' . static::class . "::$attribute' attribute."
                            . ' The attribute options must be an Integer.'
                    );
                }
            }
        }

        return $attributeOptions[static::class];
    }

    /**
     * Returns a list of entity primary key attribute names.
     *
     * The primary key uniquely identifies an entity. It can consist of one or more attributes.
     *
     * Note, it is recommended to use `primaryKeyAttributes()` to retrieve a verified list of primary
     * key attribute names.
     *
     * By default, unless you override this function in a child class, all attribute names that have
     * the `ATTRIBUTE_OPTION_PRIMARYKEY` flag will be retrieved from `attributeOptions()` and returned.
     *
     * When you override this function, the return value must be an array of string:
     * ```php
     * ['attribute1', 'attribute2', ...]
     * ```
     *
     * Note, in order to inherit a primary key defined in the parent class, a child class needs to merge
     * the parent primary key with child primary key using functions such as `array_merge()`.
     *
     * @return array<int,string> a list of entity primary key attribute names.
     *
     * @see attributeOptions()
     * @see ATTRIBUTE_OPTION_PRIMARYKEY
     */
    protected static function primaryKey(): array
    {
        /**
         * A shared cache of the list of primary key attribute names of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => primaryKeyList).
         *
         * @var array<string,array<int,string>>
         */
        static $primaryKey = [];

        if (!isset($primaryKey[static::class])) {
            // Primary key attribute names extraction.
            $primaryKey[static::class] = array_keys(
                array_filter(
                    static::attributeOptions(),
                    function (int $options): bool {
                        return ($options & static::ATTRIBUTE_OPTION_PRIMARYKEY) > 0;
                    }
                )
            );
        }

        return $primaryKey[static::class];
    }

    /**
     * Returns a verified list of entity primary key attribute names.
     *
     * The primary key uniquely identifies an entity. It can consist of one or more attributes.
     *
     * Verifies all primary key attribute names specified in `primaryKey()`. They must be listed in
     * `attributeList()`.
     *
     * @return array<int,string> a verified list of entity primary key attribute names.
     *
     * @see primaryKey()
     */
    final public static function primaryKeyAttributes(): array
    {
        /**
         * A shared cache of the verified list of primary key attribute names of all child classes
         * (for php 8.1 and later), indexed by child class name (childClassName => primaryKeyList).
         *
         * @var array<string,array<int,string>>
         */
        static $primaryKeyAttributes = [];

        if (!isset($primaryKeyAttributes[static::class])) {
            // Primary key attribute names extraction.
            $primaryKeyAttributes[static::class] = array_values(static::primaryKey());
            // Primary key attribute names validation.
            $attributeList = static::attributeList();
            foreach ($primaryKeyAttributes[static::class] as $attribute) {
                if (!in_array($attribute, $attributeList)) {
                    throw new \RuntimeException(
                        "Unknown attribute '$attribute' in '" . static::class . '::primaryKey()\'.'
                    );
                }
            }
        }

        return $primaryKeyAttributes[static::class];
    }

    /**
     * Returns default values for entity attributes.
     *
     * Note, it is recommended to use `attributeDefaults()` to retrieve correctly computing default
     * attribute values.
     *
     * By default, unless you override this function in a child class, all `default` specification
     * values will be retrieved from `attributeSpecifications()` and returned (missing default values
     * will be filled with `null`).
     *
     * When you override this function, the return value must be an associative array of default values
     * (attribute => defaultValue). Note, you must be sure to set default values for ALL attributes:
     * ```php
     * [
     *     'attribute1' => defaultValue1,
     *     'attribute2' => null,
     *     'attribute3' => defaultValue3,
     *     ...
     * ]
     * ```
     * The default value may be a simple value, callable or the name of a static method of the current
     * class, in the latter cases it will be computed at runtime (see `attributeDefaults()`).
     *
     * Note, in order to inherit default values defined in the parent class, a child class needs to
     * merge the parent default values with child default values using functions such as `array_merge()`:
     * ```php
     * return array_merge(parent::defaults(), [
     *     'name' => 'new default name',
     *     'price' => 10,
     *     'createdAt' => fn() => new \DateTimeImmutable(),
     * ]);
     * ```
     *
     * @return array<string,mixed> default values of entity attributes (attribute => defaultValue).
     *
     * @see attributeSpecifications()
     * @see attributeDefaults()
     * @see internalCreate()
     */
    protected static function defaults(): array
    {
        /**
         * A shared cache of default values for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => defaultValue]).
         *
         * @var array<string,array<string,mixed>>
         */
        static $defaults = [];

        if (!isset($defaults[static::class])) {
            // Extraction default attribute values.
            $defaults[static::class] =
                static::extractAttributeSpecificationValues('default', true);
        }

        return $defaults[static::class];
    }

    //  * Note, the default `null` values for attributes that have calss set in `attributeClasses()` and
    //  * a `notNull` validator in `attributeValidators()` will be replaced with an empty array `[]`.
    // *
    /**
     * Returns static default values for entity attributes.
     *
     * Missing and callable default values will be filled with `null` (to preserve attribute order).
     *
     * @return array<string,mixed> static default values of entity attributes
     * (attribute => staticDefaultValue).
     *
     * @see defaults()
     * @see attributeDefaults()
     */
    private static function defaultsStatic(): array
    {
        /**
         * A shared cache of static default values for entity attributes of all child classes
         * (for php 8.1 and later), indexed by child class name
         * (childClassName => [attribute => staticDefaultValue]).
         *
         *  @var array<string,array<string,mixed>>
         */
        static $defaultsStatic = [];

        if (!isset($defaultsStatic[static::class])) {
            // Selection of static default values (missing and callable values are filled with `null`).
            $defaultsStatic[static::class] = array_merge(
                array_fill_keys(static::attributeList(), null),
                array_diff_key(static::defaults(), static::defaultsCallable())
            );
            // // Replacing default values `null` with `[]` for attributes with `notNull` validator and `class` set.
            // $attributeClasses = static::attributeClasses();
            // $attributeValidators = static::attributeValidators();
            // foreach ($defaultsStatic[static::class] as $attribute => &$defaultValue) {
            //     if (
            //         isset($attributeClasses[$attribute])
            //         && (null === $defaultValue)
            //         && (array_key_exists('notNull', $attributeValidators[$attribute] ?? []))
            //     ) {
            //         $defaultValue = [];
            //     }
            // }
            // unset($defaultValue);
        }

        return $defaultsStatic[static::class];
    }

    /**
     * Returns callable default values for entity attributes.
     *
     * @return array<string,defaultCallable> callable default values of entity attributes
     * (attribute => \Closure).
     *
     * @template defaultCallable of \Closure(): mixed
     *
     * @see defaults()
     * @see attributeDefaults()
     */
    private static function defaultsCallable(): array
    {
        /**
         * A shared cache of callable default values for entity attributes of all child classes
         * (for php 8.1 and later), indexed by child class name (childClassName => [attribute => \Closure]).
         *
         *  @var array<string,array<string,\Closure>>
         */
        static $defaultsCallable = [];

        if (!isset($defaultsCallable[static::class])) {
            // Selection of callable default values.
            $defaultsCallable[static::class] =
                static::normalizeCallables(static::defaults(), false, true, 'default');
        }

        return $defaultsCallable[static::class];
    }

    /**
     * Returns correctly computed default values for entity attributes.
     *
     * All callable default values will be computed.
     *
     * @return array<string,mixed> default values of entity attributes (attribute => defaultValue).
     *
     * @see defaultsStatic()
     * @see defaultsCallable()
     */
    final public static function attributeDefaults(): array
    {
        $defaultValues = static::defaultsStatic();
        foreach (static::defaultsCallable() as $attribute => $defaultCallable) {
            $defaultValues[$attribute] = $defaultCallable();
        }
        return $defaultValues;
    }

    /**
     * Returns a list of getters for entity attributes.
     *
     * Note, it is recommended to use `attributeGetters()` to retrieve verified attribute getters.
     *
     * Note, getters only apply to the massive way of getting attribute values using
     * `internalGetAttributes()`. And can be used to convert attribute values into a data transfer
     * format.
     *
     * By default, unless you override this function in a child class, all `getter` specification
     * values will be retrieved from `attributeSpecifications()` and returned.
     *
     * When you override this function, the return value must be an associative array of getters
     * (attribute => getter):
     * ```php
     * [
     *     'attribute1' => getter1,
     *     'attribute2' => getter2,
     *     ...
     * ]
     * ```
     * The getter must be a `callable` or static method name of the current class and must have the
     * following specification:
     * ```php
     * callable(string $attribute, mixed $value, int $options): mixed;
     * ```
     *
     * Note, in order to inherit getters defined in the parent class, a child class needs to
     * merge the parent getters with child getters using functions such as `array_merge()`.
     *
     * @return array<string,getter|string> list of getters for entity attributes (attribute => getter).
     *
     * @template getter of callable(string $attribute, mixed $value, int $options): mixed
     *
     * @see attributeSpecifications()
     * @see attributeGetters()
     */
    protected static function getters(): array
    {
        /**
         * A shared cache of getters for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => getter]).
         *
         * @var array<string,array<string,callable|string>>
         */
        static $getters = [];

        if (!isset($getters[static::class])) {
            // Attribute getters extraction.
            $getters[static::class] = static::extractAttributeSpecificationValues('getter', true);
        }

        return $getters[static::class];
    }

    /**
     * Returns a list of verified getters for entity attributes.
     *
     * Verifies all attribute getters specified in `getters()` and casts them to `\Closure`.
     *
     * @return array<string,getter> list of verified getters for entity attributes (attribute => getter).
     *
     * @template getter of \Closure(string $attribute, mixed $value, int $options): mixed
     *
     * @see getters()
     */
    final public static function attributeGetters(): array
    {
        /**
         * A shared cache of verified getters for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => getter]).
         *
         * @var array<string,array<string,\Closure>>
         */
        static $attributeGetters = [];

        if (!isset($attributeGetters[static::class])) {
            // Selection of valid callable getters.
            $attributeGetters[static::class] =
                static::normalizeCallables(static::getters(), true, true, 'getter');
        }

        return $attributeGetters[static::class];
    }

    /**
     * Returns a list of setters for entity attributes.
     *
     * Note, it is recommended to use `attributeSetters()` to retrieve verified attribute setters.
     *
     * Note, setters only apply to the massive way of setting attribute values using
     * `internalSetAttributes()`. And can be used to create attribute values from a data transfer
     * format.
     *
     * By default, unless you override this function in a child class, all `setter` specification
     * values will be retrieved from `attributeSpecifications()` and returned.
     *
     * When you override this function, the return value must be an associative array of setters
     * (attribute => setter):
     * ```php
     * [
     *     'attribute1' => setter1,
     *     'attribute2' => setter2,
     *     ...
     * ]
     * ```
     * The setter must be a `callable` or static method name of the current class and must have the
     * following specification:
     * ```php
     * callable(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool;
     * ```
     * Setter should not directly modify the entity attribute, it only prepares a new attribute value
     * and returns `true` if the preparation passed without error, or `false` otherwise.
     *
     * Note, in order to inherit setters defined in the parent class, a child class needs to
     * merge the parent setters with child setters using functions such as `array_merge()`.
     *
     * @return array<string,setter|string> list of setters for entity attributes (attribute => setter).
     *
     * @template setter of callable(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
     *
     * @see attributeSpecifications()
     * @see attributeSetters()
     */
    protected static function setters(): array
    {
        /**
         * A shared cache of setters for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => setter]).
         *
         * @var array<string,array<string,callable|string>>
         */
        static $setters = [];

        if (!isset($setters[static::class])) {
            // Attribute setters extraction.
            $setters[static::class] = static::extractAttributeSpecificationValues('setter', true);
        }

        return $setters[static::class];
    }

    /**
     * Returns a list of verified setters for entity attributes.
     *
     * Verifies all attribute setters specified in `setters()` and casts them to `\Closure`.
     *
     * @return array<string,setter> list of verified setters for entity attributes (attribute => setter).
     *
     * @template setter of \Closure(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
     *
     * @see setters()
     */
    protected static function attributeSetters(): array
    {
        /**
         * A shared cache of verified setters for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => setter]).
         *
         * @var array<string,array<string,\Closure>>
         */
        static $attributeSetters = [];

        if (!isset($attributeSetters[static::class])) {
            // Selection of valid callable setters.
            $attributeSetters[static::class] =
                static::normalizeCallables(static::setters(), true, true, 'setter');
        }

        return $attributeSetters[static::class];
    }

    /**
     * Returns a list of generators for entity attributes.
     *
     * Note, it is recommended to use `attributeGenerators()` to retrieve verified attribute generators.
     *
     * Note, generators only apply to the massive way of setting attribute values using
     * `internalSetAttributes()`. And unlike a setter, the generator will be called anyway, even if no
     * new attribute value is passed in.
     *
     * By default, unless you override this function in a child class, all `generator` specification
     * values will be retrieved from `attributeSpecifications()` and returned.
     *
     * When you override this function, the return value must be an associative array of generators
     * (attribute => generators):
     * ```php
     * [
     *     'attribute1' => generator1,
     *     'attribute2' => generator2,
     *     ...
     * ]
     * ```
     * The generator must be a `callable` or static method name of the current class and must have the
     * following specification:
     * ```php
     * callable(mixed $newValue): mixed;
     * ```
     * Generator should not directly modify the entity attribute, it only generates and returns the new
     * attribute value. Generator can use the passed `$newValue` parameter to generate a new value.
     *
     * Note, in order to inherit generators defined in the parent class, a child class needs to
     * merge the parent generators with child generators using functions such as `array_merge()`.
     *
     * @return array<string,generator|string> a list of generators for entity attributes
     * (attribute => generator).
     *
     * @template generator of callable(mixed $newValue): mixed
     *
     * @see attributeSpecifications()
     * @see attributeGenerators()
     */
    protected static function generators(): array
    {
        /**
         * A shared cache of generators for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => generator]).
         *
         * @var array<string,array<string,callable|string>>
         */
        static $generators = [];

        if (!isset($generators[static::class])) {
            // Attribute generators extraction.
            $generators[static::class] = static::extractAttributeSpecificationValues('generator', true);
        }

        return $generators[static::class];
    }

    /**
     * Returns a list of verified generators for entity attributes.
     *
     * Verifies all attribute generators specified in `generators()` and casts them to `\Closure`.
     *
     * @return array<string,generator> list of verified generators for entity attributes
     * (attribute => generator).
     *
     * @template generator of \Closure(mixed $newValue): mixed
     *
     * @see generators()
     */
    final public static function attributeGenerators(): array
    {
        /**
         * A shared cache of verified generators for entity attributes of all child classes (for php 8.1
         * and later), indexed by child class name (childClassName => [attribute => generator]).
         *
         * @var array<string,array<string,\Closure>>
         */
        static $attributeGenerators = [];

        if (!isset($attributeGenerators[static::class])) {
            // Selection of valid callable generators.
            $attributeGenerators[static::class] =
                static::normalizeCallables(static::generators(), true, true, 'generator');
        }

        return $attributeGenerators[static::class];
    }

    /**
     * Checks whether the attribute is read-only.
     *
     * @param string $attribute attribute name.
     *
     * @return bool `true` if the attribute is read-only, `false` otherwise.
     *
     * @see ATTRIBUTE_OPTION_READONLY
     */
    protected static function isReadOnlyAttribute(string $attribute): bool
    {
        $options = static::attributeOptions()[$attribute] ?? 0;
        return ($options & static::ATTRIBUTE_OPTION_READONLY) > 0;
    }

    /**
     * {@inheritdoc}
     *
     * If a getter is defined for an attribute, the attribute value will be retrieved using that getter
     * (see `attributeGetters()`).
     *
     * {@inheritdoc}
     */
    protected function getAttribute(string $attribute, int $options): mixed
    {
        $attributeGetters = static::attributeGetters();
        if (isset($attributeGetters[$attribute])) {
            return $attributeGetters[$attribute]($attribute, $this->$attribute, $options);
        }

        return parent::getAttribute($attribute, $options);
    }

    /**
     * {@inheritdoc}
     *
     * - If a setter is defined for an attribute, the attribute value will be prepared using that setter
     * (see `attributeSetters()`).
     *
     * {@inheritdoc}
     *
     * @see attributeSetters()
     */
    protected function prepareAttributeValue(string $attribute, mixed &$newValue, OperationResult $result): bool
    {
        $attributeSetters = static::attributeSetters();
        if (isset($attributeSetters[$attribute])) {
            return $attributeSetters[$attribute]($attribute, $this->recordPath, $newValue, $result);
        }

        return parent::prepareAttributeValue($attribute, $newValue, $result);
    }

    /**
     * Excludes read-only attributes.
     *
     * Read-only attributes can only be set once (as long as their value is `null`).
     *
     * @param array<string,mixed> associative array of new attribute values (attribute => newValue)
     * to be processed.
     * @param string $warning warning message. If an empty string, no warning is generated.
     *
     * @return void
     *
     * @see isReadOnlyAttribute()
     *
     */
    protected function excludeReadonlyAttributes(array &$newValues, string $warning = ''): void
    {
        foreach ($newValues as $attribute => $newValue) {
            if (static::isReadOnlyAttribute($attribute) && isset($this->$attribute)) {
                unset($newValues[$attribute]);
                if (!empty($warning)) {
                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    trigger_error(
                        $warning . ' \'' . static::class . "::$attribute'"
                            . " in {$trace[3]['file']}:{$trace[3]['line']}.",
                        E_USER_WARNING
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * - Excludes read-only attributes (see `excludeReadonlyAttributes()`).
     * - Generates attribute values that have generators specified (see `attributeGenerators()`).
     * - {@inheritdoc}
     */
    protected function prepareAttributeValues(array &$newValues, OperationResult $result): bool
    {
        $this->excludeReadonlyAttributes(
            $newValues,
            'Can not modify read-only attribute'
        );

        $attributeGenerators = static::attributeGenerators();
        foreach ($attributeGenerators as $attribute => $generator) {
            $newValues[$attribute] = $generator($newValues[$attribute] ?? null);
        }

        return parent::prepareAttributeValues($newValues, $result);
    }

    /**
     * Massive way of validating and assigning new values to record attributes.
     *
     * Incorrect attribute names from the `$newValues` array will be skipped.
     *
     * New values will be prepared and validated before assignment, and all validation errors will
     * be added to the `$result` object (see `OperationResult`).
     *
     * @param array<string,mixed>|DataTransferObject $newValues new attribute values that should be assigned
     * to the record.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return bool whether the validation and assignment of values passed without errors.
     *
     * @see internalSetAttributes()
     * @see OperationResult
     */
    public function setAttributes(array|DataTransferObject $newValues, OperationResult $result): bool
    {
        $newValues = DataTransferObject::DTOtoArray($newValues);
        return parent::internalSetAttributes($newValues, $result);
    }
}
