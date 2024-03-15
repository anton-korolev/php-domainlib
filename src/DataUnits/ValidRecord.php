<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

use DomainLib\Results\OperationResult;
use DomainLib\Validators\BaseValidators;

use RuntimeException;
use Closure;

/**
 * @todo:
 * - Add options to attribute validators: such as min, max, minLength, maxLength, pattern, etc.
 * - Add error message for the attribute validators (each individually or common to all).
 * - Add a not (!) operator for validators. Or add separate validators like 'notEmpty'?
 * (So far, the second variant has been implemented.)
 * - Add a nullable (?) operator for validators. Or add separate validators like 'nullableString'?
 * (So far, the second variant has been implemented.)
 */

/**
 * Record with attribute validation.
 *
 * Additional implements the following features:
 * - Validate new attribute values (including attributes of nested records) before creating or updating
 * a record (see `validateAttributeValues()`).
 * - Create a new or update an existing record (including nested records) from an associative array
 * (see `internalCreate()` and `internalSetAttributes()`).
 * - Create a new record (including nested records) from the DTO (see `createFromDTO()`).
 * - Convert the record (including nested records) to a DTO (see `toDTO()`).
 *
 * Supports the following attribute specifications:
 * - `class` - the attribute's class name.
 * - `validators` - list of attribute validators.
 */
abstract class ValidRecord extends AbstractRecord implements DataTransferInterface
{
    /**
     * Defines the corresponding DTO class for the current Record class.
     *
     * You can override this constant in a child class.
     *
     * Must be an corresponding DTO class name (`DataTransferObject::class`) or an empty string.
     *
     * If `DTO_CLASS` is an empty string, then the `toDTO()` function will return an associative array
     * of record attributes. Otherwise, the `toDTO()` function will return an object instance of the
     * corresponding DTO class.
     *
     * Note, you can override the `toDTO()` function to cast the return value to the correct DTO class.
     *
     * @var DataTransferObject DTO class name. By default is emty string.
     *
     * @see toDTO()
     */
    protected const DTO_CLASS = '';

    /**
     * Flag for using `DataTransferInterface` in `internalGetAttributes()`.
     *
     * If it is set, the attributes that implement the `DataTransferInterface` will be returned by
     * the `DataTransferInterface::toDTO()` function.
     *
     * @var int
     * @see getAttribute()
     */
    final public const GETTER_USE_DATA_TRANSFER_INTERFACE = 1 << 1;

    /**
     * {@inheritdoc}
     */
    public const GETTER_DEFAULT_OPTIONS = parent::GETTER_DEFAULT_OPTIONS
        | self::GETTER_USE_DATA_TRANSFER_INTERFACE
        | self::GETTER_CLONE_OBJECTS;

    /**
     * Allow only string names for attribute validators.
     *
     * See `validators()` for more details.
     *
     * @var bool
     * @see validators()
     */
    protected const STRICT_VALIDATOR_NAMES = true;

    /**
     * Path to the current record.
     *
     * @var string
     */
    protected readonly string $recordPath;

    /**
     * Returns a verified array of `callable` values.
     *
     * Verifies and normalizes the `callable` values from the input array.
     *
     * The following values will be normalized in the input array:
     * - names of static methods of the current class will be converted to `\Closure`;
     * - names listed in `$callableMap` will be replaced by the corresponding value.
     *
     * @param array<int|string,mixed> $values input array to extract the callable values.
     * @param bool $callableOnly allow only `callable` values.
     * @param bool $strictNames allow only string keys.
     * @param string $specName name of the specification.
     * @param string|null $attribute attribute name. Defaults to null, meaning attribute names will
     * be taken from the keys of the `$values` array.
     * @param array<string,\Closure>|null $callableMap additional name map of `callable` values
     * (name => \Closure).
     *
     * @return array<int|string,\Closure> array of extracted callable values.
     *
     * @throws RuntimeException if an invalid `callable` is defined.
     */
    protected static function normalizeCallables(
        array $values,
        bool $callableOnly,
        bool $strictNames,
        string $specName,
        string|null $attribute = null,
        array|null $callableMap = null,
    ): array {
        $result = [];

        // // You can pass $callableMap only if no empty $attribute passed???
        // if (null !== $callableMap && empty($attribute)) {
        //     throw new \RuntimeException(
        //         'Can not pass $callableMap for empty $attribute.'
        //         // . static::class . '::normalizeCallables().'
        //     );
        // }

        // // Replaced integer keys with string keys (from $value, or 'callable_#') as aliases for `callable`.
        // /** @var int $c number of unnamed callables. */
        // $c = 0;
        foreach ($values as $key => $value) {
            if (
                is_string($value)
                && isset($callableMap[$value])
            ) {
                // It is a value from the callable map.
                $result[is_string($key) ? $key : $value] = $callableMap[$value];
                // if (is_callable($callableMap[$value])) {
                //     $result[is_string($key) ? $key : $value] =
                //         is_a($callableMap[$value], \Closure::class)
                //         ? $callableMap[$value]
                //         : \Closure::fromCallable($callableMap[$value]);
                // } else {
                //     throw new \RuntimeException(
                //         'Invalid callableMap value \'' . $value . '\': '
                //             . var_export($callableMap[$value], true) . ' at '
                //             . static::class . '::' . ($attribute ?? $key) . "[$specName]"
                //     );
                // }
            } elseif (is_callable([static::class, $value])) {
                // It is a static method of the current class.
                /** @var string $value */
                $result[is_string($key) ? $key : $value] =
                    \Closure::fromCallable([static::class, $value]);
            } elseif (is_callable($value)) {
                // It is a callable value.
                if ($strictNames && !is_string($key)) {
                    throw new \RuntimeException(
                        'Callable name \'' . $key . '\' must be a string at '
                            . static::class . '::' . ($attribute ?? $key) . "[$specName]"
                    );
                }
                // $result[is_string($key) ? $key
                //     : (is_string($value) ? $value : 'callable_' . ++$c)] = $value;
                $result[$key] =
                    is_a($value, \Closure::class)
                    ? $value
                    : \Closure::fromCallable($value);
            } elseif ($callableOnly) {
                throw new \RuntimeException('Invalid callable ' . var_export($value, true) . ' at '
                    . static::class . '::' . ($attribute ?? $key) . "[$specName]");
            }
        }

        return $result;
    }

    /**
     * Constructor - performs object initialization.
     *
     * - Sets path to the record.
     *
     * @param string $recordPath a path to the record.
     *
     * @return void
     */
    final private function __construct(string $recordPath)
    {
        // Seting path to the record.
        $this->recordPath = $recordPath;
    }

    /**
     * Internal factory method to create an instance of `ValidRecord` from an associative array
     * (attribute => value).
     *
     * New values will be prepared and validated before assignment, and all validation errors will
     * be added to the `$result` object (see `OperationResult`).
     *
     * Supports automatic creation of internal records.
     *
     * TODO:
     * - If necessary, implement validation before creating the object.
     *
     * @param array<string,mixed> $values associative array of attribute values (attribute => value) to
     * create a record.
     * @param string $recordPath path to the record to be created.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return static|null a new instance of `ValidRecord` if the record creation passed
     * without errors, or `null` otherwise.
     *
     * @see internalSetAttributes()
     * @see OperationResult
     */
    protected static function internalCreate(array $values, string $recordPath, OperationResult $result): static|null
    {
        $newInstance = new static($recordPath);
        if ($newInstance->internalSetAttributes($values, $result)) {
            return $newInstance;
        }
        return null;
    }

    /**
     * Factory method to create an instance of `ValidRecord` from an associative array
     * (attribute => value) or from a Data Transfer Object.
     *
     * New values will be prepared and validated before assignment, and all validation errors will
     * be added to the `$result` object (see `OperationResult`).
     *
     * Supports automatic creation of internal records.
     *
     * @param array<string,mixed>|DataTransferObject $values attribute values to create a record.
     * @param string $recordPath path to the record to be created.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return static|null a new instance of `ValidRecord` if the record creation passed without errors,
     * or `null` otherwise.
     *
     * @see internalCreate()
     * @see OperationResult
     */
    public static function createFromDTO(
        array|DataTransferObject $values,
        string $recordPath,
        OperationResult $result
    ): static|null {
        $values = DataTransferObject::DTOtoArray($values);
        return static::internalCreate($values, $recordPath, $result);
    }

    /**
     * Converts a record to an associative array (attribute => value) or to a Data Transfer Object.
     *
     * If an corresponding DTO class is specified, the record will be automatically converted to a DTO
     * (see `DTO_CLASS`).
     *
     * Supports automatic conversion of internal records to an associative arrays or Data Transfer Objects
     * (if the corresponding DTO classes are set in the internal records).
     *
     * @param array<int,string>|null $attributes list of attributes whose value needs to be converted.
     * Defaults to null, meaning all attributes listed in `attrbutes()` will be converted.
     * If it is an array, only the attributes in the array will be converted.
     *
     * @return array<string,mixed>|DataTransferObject converted record as an associative array
     * (attribute => value) or Data Transfer Object.
     *
     * @see DTO_CLASS
     * @see getAttribute()
     * @see internalGetAttributes()
     * @see DataTransferObject::createFromArray()
     */
    public function toDTO(array|null $attributes = null): array|DataTransferObject
    {
        if (!empty(static::DTO_CLASS)) {
            return (static::DTO_CLASS)::createFromArray($this->internalGetAttributes($attributes));
        } else {
            return $this->internalGetAttributes($attributes);
        }
    }

    /**
     * Returns a list of record attribute class names.
     *
     * Note, it is recommended to use `attributeClasses()` to retrieve verified attribute classes.
     *
     * Some attributes themselves may be inherited from the `ValidRecord` class (or implement
     * `DataTransferInterface`). And to automatically create all internal records, you must specify
     * their classes.
     *
     * Note, all internal records must implementation `DataTransferInterface`.
     *
     * By default, unless you override this function in a child class, all `class` specification values
     * will be retrieved from `attributeSpecifications()` and returned.
     *
     * When you override this function, the return value must be an associative array of strings:
     * ```php
     * [
     *     'attribute1' => 'className1',
     *     'attribute2' => 'className2',
     *     ...
     * ]
     * ```
     *
     * Note, in order to inherit class names defined in the parent class, a child class needs to
     * merge the parent class names with child class names using functions such as `array_merge()`.
     *
     * @return array<string,string> a list of attribute class names (attribute => class).
     *
     * @see attributeSpecifications()
     * @see attributeClasses()
     */
    protected static function classes(): array
    {
        /**
         * A shared cache of attribute class names of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => [attribute => class]).
         *
         * @var array<string,array<string,string>>
         */
        static $classes = [];

        if (!isset($classes[static::class])) {
            // Attribute classes extraction.
            $classes[static::class] =
                static::extractAttributeSpecificationValues('class', true);
        }

        return $classes[static::class];
    }

    /**
     * Returns a list of verified record attribute class names.
     *
     * Validates all class names specified in `classes()`. They must implement `DataTransferInterface`.
     *
     * @return array<string,DataTransferInterface> a list of verified attribute class names
     * (attribute => class).
     *
     * @throws RuntimeException if an invalid class is defined for the attribute.
     *
     * @see classes()
     */
    final public static function attributeClasses(): array
    {
        /**
         * A shared cache of verified attribute class names of all child classes (for php 8.1 and later),
         * indexed by child class name (ChildClassName => [attribute => class]).
         *
         * @var array<string,array<string,DataTransferInterface>>
         */
        static $attributeClasses = [];

        if (!isset($attributeClasses[static::class])) {
            $attributeClasses[static::class] = static::classes();
            // Attribute classes validation.
            foreach ($attributeClasses[static::class] as $attribute => $attributeClass) {
                if (!is_subclass_of($attributeClass, DataTransferInterface::class)) {
                    throw new \RuntimeException(
                        'Invalid class at \'' . static::class . "::$attribute' attribute."
                            . ' The attribute must implement ' . DataTransferInterface::class . '.'
                    );
                }
            }
        }

        return $attributeClasses[static::class];
    }

    /**
     * Returns a map of named validators used to validate attribute values (validatorName => validator).
     *
     * Note, it is recommended to use `attributeValidatorsMap()` to retrieve verified map of named
     * attribute validators.
     *
     * The validator must be callable and have the following specification:
     * ```php
     * callable(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool;
     * ```
     * And return `true` if the validation passed without error, or `false` otherwise.
     *
     * By default, unless you override this function in a child class, the validators map from
     * `BaseValidators::callableMap()` will be returned.
     *
     * When you override this function, the return value must be an associative array of validator:
     * ```php
     * [
     *     'validatorName1' => validator1,
     *     'validatorName2' => validator2,
     *     ...
     * ]
     * ```
     *
     * Note, in order to inherit class names defined in the parent class, a child class needs to
     * merge the parent class names with child class names using functions such as `array_merge()`.
     *
     * @return array<string,validator> map of named validators used to validate attribute values
     * (validatorName => validator).
     *
     * @template validator of callable(string $attribute,string $path,mixed &$newValue,OperationResult|null $result = null): bool
     *
     * @see attributeValidatorsMap()
     */
    protected static function validatorsMap(): array
    {
        return BaseValidators::callableMap();
    }

    /**
     * Returns a verified map of named validators used to validate attribute values (validatorName => validator).
     *
     * Verifies the map of named validators specified in `validatorsMap()` and casts them to `\Closure` if needed.
     *
     * @return array<string,validator> verified map of named validators used to validate attribute values
     * (validatorName => validator).
     *
     * @template validator of \Closure(string $attribute,string $path,mixed &$newValue,OperationResult|null $result = null): bool
     *
     * @see validatorsMap()
     * @see validators()
     */
    final public static function attributeValidatorsMap(): array
    {
        /**
         * A shared cache of attribute validators map list of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => [attributeValidatorsMap]).
         *
         * @var array<string,array<string,\Closure>>
         */
        static $attributeValidatorsMap = [];

        if (!isset($attributeValidatorsMap[static::class])) {

            $attributeValidatorsMap[static::class] =  static::validatorsMap();

            // Attribute validators map checking.
            foreach ($attributeValidatorsMap[static::class] as $validatorName => &$validator) {
                // The validator name must be a string.
                if (!is_string($validatorName)) {
                    throw new \RuntimeException(
                        "Invalid validator index '$validatorName' at '" . static::class . '::validatorsMap()\'.'
                            . ' The validator index must be a string.'
                    );
                }

                // The validator must be a callable.
                // if (!is_a($validator, \Closure::class)) {
                if (!is_callable($validator)) {
                    throw new \RuntimeException(
                        "Invalid validator '$validatorName' at '" . static::class . '::validatorsMap()\'.'
                            . ' The validator must be callable.'
                    );
                }

                // Convert validatopr to \Closure if needed.
                if (!is_a($validator, \Closure::class)) {
                    $validator = \Closure::fromCallable($validator);
                }
            }
            unset($validator);
        }

        return $attributeValidatorsMap[static::class];
    }

    /**
     * Returns a list of record attribute validators.
     *
     * Note, it is recommended to use `attributeValidators()` to retrieve verified attribute validators.
     *
     * Validators will be applied to new attribute values to validate them before assignment.
     *
     * A validator can be specified by a string index from the validators map (see `attributeValidatorsMap()`),
     * the name of a static method of the current class, or `callable`.
     *
     * All validators must have the following specification:
     * ```php
     * callable(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool;
     * ```
     * And return `true` if the validation passed without error, or `false` otherwise.
     *
     * By default, unless you override this function in a child class, all `validators` specification
     * values will be retrieved from `attributeSpecifications()` and returned.
     *
     * Note, in strict validator names mode (see `STRICT_VALIDATOR_NAMES`), all validators specified
     * by the `callable` type must have a string index as their name. In other cases, a string value
     * will be used as the name.
     *
     * When you override this function, the return value must be a two-dimensional associative array
     * (attribute => [validatorList]):
     * ```php
     * [
     *     'attribute1' => ['validator1', 'validator2', callable, ...],
     *     'attribute2' => ['validator2', 'validatorName' => callable, ...],
     *     ...
     * ]
     * ```
     * Note, in order to inherit validators defined in the parent class, a child class needs to
     * merge the parent validators with child validators using functions such as `array_merge()`.
     *
     * @return array<string,array<int|string,string|validator>> a list of record attribute
     * validators (attribute => [validatorList]).
     *
     * @template validator of callable(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
     *
     * @see attributeSpecifications()
     * @see attributeValidatorsMap()
     * @see STRICT_VALIDATOR_NAMES
     * @see attributeValidators()
     */
    protected static function validators(): array
    {
        /**
         * A shared cache of attribute validator list of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => [attribute => [validatorList]]).
         *
         * @var array<string,array<string,array<int|string,callable>>>
         */
        static $validators = [];

        if (!isset($validators[static::class])) {
            // Attribute validators extraction.
            $validators[static::class] =
                static::extractAttributeSpecificationValues('validators', false);
        }

        return $validators[static::class];
    }

    /**
     * Returns a list of verified record attribute validators.
     *
     * Verifies all attribute validators specified in `validators()` and casts them to `\Closure`.
     *
     * @return array<string,array<int|string,validator>> a list of verified record attribute
     * validators (attribute => [validatorList]).
     *
     * @template validator of \Closure(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
     *
     * @see validators()
     */
    final public static function attributeValidators(): array
    {
        /**
         * A shared cache of verified attribute validator list of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => [attributeName => [validatorList]]).
         *
         * @var array<string,array<string,array<int|string,\Closure>>>
         */
        static $attributeValidators = [];

        if (!isset($attributeValidators[static::class])) {
            $attributeValidators[static::class] = static::validators();
            // Checking attribute validators.
            $attributeValidatorsMap = static::attributeValidatorsMap();
            foreach ($attributeValidators[static::class] as $attribute => &$validators) {
                $validators = static::normalizeCallables(
                    $validators,
                    true,
                    static::STRICT_VALIDATOR_NAMES,
                    'validators',
                    $attribute,
                    $attributeValidatorsMap
                );
                if ([] === $validators) {
                    unset($attributeValidators[static::class][$attribute]);
                }
            }
            unset($validators);
        }

        return $attributeValidators[static::class];
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     * - GETTER_USE_DATA_TRANSFER_INTERFACE - whether to use an `DataTransferInterface` for attribute
     * getting. If this flag is set, the attributes that implement the DataTransferInterface will be
     * returned by the `DataTransferInterface::toDTO()` function.
     *
     */
    protected function getAttribute(string $attribute, int $options): mixed
    {
        return match (true) {
            (
                ($options & self::GETTER_USE_DATA_TRANSFER_INTERFACE)
                && ($this->$attribute instanceof DataTransferInterface)
            ) => $this->toDataTransferInterface($this->$attribute)->toDTO(),

            default => parent::getAttribute($attribute, $options),
        };
    }

    /**
     * Helper for casting object type to DataTransferInterface.
     *
     * The object must first be checked for a DataTransferInterface implementation with
     * ($o instanceof DataTransferInterface).
     *
     * @param object $o object to type cast
     *
     * @return DataTransferInterface objet with type cast
     */
    final protected static function toDataTransferInterface(object $o): DataTransferInterface
    {
        return $o;
    }

    /**
     * Prepares new attribute value before validation and assignment.
     *
     * - If the attribute has a class defined in `attributeClasses()`, they will be created using
     * `DataTransferInterface::createFromDTO()`.
     *
     * @param string $attribute a attribute name.
     * @param mixed &$newValue the new attribute value to be prepared.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return bool whether the preparation of new attribute value passed without errors.
     *
     * @see attributeClasses()
     * @see OperationResult
     */
    protected function prepareAttributeValue(string $attribute, mixed &$newValue, OperationResult $result): bool
    {
        $hasError = false;

        $attributeClasses = static::attributeClasses();
        if (
            isset($attributeClasses[$attribute])
            && (null !== $newValue)
            && !($newValue instanceof (DataTransferInterface::class))
        ) {
            if (is_array($newValue) || ($newValue instanceof DataTransferObject)) {
                $newValue =
                    $attributeClasses[$attribute]::createFromDTO(
                        $newValue,
                        $result->fullName($attribute, $this->recordPath),
                        $result
                    );
                $hasError = (null === $newValue);
            } else {
                $result->addError(
                    $result::VALIDATION_ERROR,
                    $result->fullName($attribute, $this->recordPath),
                    'The ' . $result->fullName($attribute, $this->recordPath, true) . ' type is invalid.',
                );
                $newValue = null;
                $hasError = true;
            }
        }
        /* elseif ( is_object($newValue) ) {
                $newValue = clone $newValue;
        } /**/

        return !$hasError;
    }

    /**
     * Prepares new attribute values before validation and assignment.
     *
     * See `prepareAttributeValue()` for more information about preparing new attribute values.
     *
     * @param array<string,mixed> &$newValues associative array of new attribute values
     * (attribute => newValue) to be prepared.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return bool whether the preparation of new attribute values passed without errors.
     *
     * @see prepareAttributeValue()
     * @see OperationResult
     */
    protected function prepareAttributeValues(array &$newValues, OperationResult $result): bool
    {
        $hasError = false;

        foreach ($newValues as $attribute => &$newValue) {
            if (!$this->prepareAttributeValue($attribute, $newValue, $result)) {
                $hasError = true;
                unset($newValues[$attribute]); // TODO: ????????? Need test unset() !!!!!!!!!!!!
                // $newValues[$attribute] = ['isValid' = false];
            } else {
                // $newValues[$attribute] = ['isValid' = true, 'value' = $newValues[$attribute]];
            }
        }
        unset($newValue);

        return !$hasError;
    }

    /**
     * Validates new attribute values before assignment.
     *
     * To each attribute to be checked, the validators defined for it in `attributeValidators()`
     * will be applied sequentially. Validation of the current attribute stopped after the first
     * error.
     *
     * @param array<string,mixed> &$newValues associative array of new attribute values
     * (attribute => newValue) to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return bool whether the validation of the values passed without errors.
     *
     * @see attributeValidators()
     */
    protected function validateAttributeValues(array &$newValues, OperationResult $result): bool
    {
        $attributeValidators = array_intersect_key(static::attributeValidators(), $newValues);
        $hasError = false;

        foreach ($attributeValidators as $attribute => $validators) {
            // $hasCurrentError = false; // For run all validators
            foreach ($validators as $validator) {
                $hasCurrentError = !$validator(
                    $attribute,
                    $this->recordPath,
                    $newValues[$attribute],
                    $result
                ); // || $hasCurrentError;
                $hasError = $hasCurrentError || $hasError;
                if ($hasCurrentError) {
                    unset($newValues[$attribute]);
                    break;
                }
            }
            // if ($hasCurrentError) { // For run all validators
            //     unset($newValues[$attribute]);
            // }
        }

        return !$hasError;
    }

    /**
     * Massive way of validating and assigning new values to record attributes.
     *
     * Incorrect attribute names from the `$newValues` array will be skipped.
     *
     * New values will be prepared and validated before assignment, and all validation errors will
     * be added to the `$result` object (see `OperationResult`).
     *
     * @param array<string,mixed> $newValues attribute values (attribute => value) that should be assigned
     * to the record.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     *
     * @return bool whether the validation and assignment of values passed without errors.
     *
     * @see internalGetAttributes()
     * @see OperationResult
     */
    protected function internalSetAttributes(array $newValues, OperationResult $result): bool
    {
        $newValues = array_intersect_key($newValues, array_flip($this->attributeList()));

        $hasError = !$this->prepareAttributeValues($newValues, $result);
        $hasError = !$this->validateAttributeValues($newValues, $result) || $hasError;
        if ($hasError) {
            return false;
        }

        foreach ($newValues as $attribute => $newValue) {
            $this->$attribute = $newValue;
        }

        return true;
    }
}
