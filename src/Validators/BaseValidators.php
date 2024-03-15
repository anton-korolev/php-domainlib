<?php

declare(strict_types=1);

namespace DomainLib\Validators;

use DomainLib\Results\OperationResult;
use DateTimeImmutable;

/**
 * A collection of simple validators.
 *
 * The validators must have the following specification:
 * ```php
 * callable(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool;
 * ```
 * And return `true` if the validation passed without error, or `false` otherwise.
 *
 */
class BaseValidators
{
    /**
     * Returns a map of implemented validators (validatorName => callable).
     *
     * Note, it is recommended to use `callableMap()` to retrieve verified map of implemented
     * validators.
     *
     * @return array<string,validator>
     *
     * @template validator of callable(string $attribute,string $path,mixed &$newValue,OperationResult|null $result = null): bool
     */
    protected static function map(): array
    {
        /* delete or append the second-to-last space */
        return [
            'emptyToNull' => static::emptyToNull(...),
            'trim' => static::trim(...),
            'notNull' => static::notNull(...),
            'notEmpty' => static::notEmpty(...),
            'isString' => static::isString(...),
            'nullableString' => static::nullableString(...),
            'isInt' => static::isInt(...),
            'nullableInt' => static::nullableInt(...),
            'isFloat' => static::isFloat(...),
            'nullableFloat' => static::nullableFloat(...),
            'isBool' => static::isBool(...),
            'nullableBool' => static::nullableBool(...),
            'email' => static::email(...),
            'nullableEmail' => static::nullableEmail(...),
            'object' => static::object(...),
            'nullableObject' => static::nullableObject(...),
            'dateTimeImmutable' => static::dateTimeImmutable(...),
            'nullableDateTimeImmutable' => static::nullableDateTimeImmutable(...),
        ];
        /*/
        return [
            'emptyToNull' => [static::class, 'emptyToNull'],
            'trim' => [static::class, 'trim'],
            'notNull' => [static::class, 'notNull'],
            'notEmpty' => [static::class, 'notEmpty'],
            'isString' => [static::class, 'isString'],
            'nullableString' => [static::class, 'nullableString'],
            'isInt' => [static::class, 'isInt'],
            'nullableInt' => [static::class, 'nullableInt'],
            'isFloat' => [static::class, 'isFloat'],
            'nullableFloat' => [static::class, 'nullableFloat'],
            'isBool' => [static::class, 'isBool'],
            'nullableBool' => [static::class, 'nullableBool'],
            'email' => [static::class, 'email'],
            'nullableEmail' => [static::class, 'nullableEmail'],
            'object' => [static::class, 'object'],
            'nullableObject' => [static::class, 'nullableObject'],
            'dateTimeImmutable' => [static::class, 'dateTimeImmutable'],
            'nullableDateTimeImmutable' => [static::class, 'nullableDateTimeImmutable'],
        ];
        /**/
    }

    /**
     * Returns a verified map of implemented validators (validatorName => \Closure).
     *
     * Verifies all validators specified in `map()` and casts them to \Closure to speed up the calling.
     *
     * @return array<string,validator> verified map of implemented validators (validatorName => \Closure).
     *
     * @template validator of \Closure(string $attribute,string $path,mixed &$newValue,OperationResult|null $result = null): bool
     */
    final public static function callableMap(): array
    {
        /**
         * A shared cache of validators map list of all child classes (for php 8.1 and later),
         * indexed by child class name (childClassName => callableMap).
         *
         * @var array<string,array<string,\Closure>>
         */
        static $callableMap = [];

        if (!isset($callableMap[static::class])) {
            $callableMap[static::class] = static::map();

            // Checking the validators map.
            foreach ($callableMap[static::class] as $validatorName => &$validator) {
                // The validator name must be a string.
                if (!is_string($validatorName)) {
                    throw new \RuntimeException(
                        "Invalid validator index '$validatorName' at '" . static::class . '::validatorsMap()\'.'
                            . ' The validator index must be a string.'
                    );
                }

                // The validator must be a callable.
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

        return $callableMap[static::class];
    }

    /**
     * Determine whether a variable is empty.
     *
     * @param mixed $value variable to be checked.
     * @return bool whether a variable is empty or not.
     */
    protected static function isEmpty(mixed $value): bool
    {
        return (is_string($value) && '' === $value)
            || (!is_int($value) && !is_float($value) && !is_bool($value) && empty($value));
    }

    /**
     * Converts the value to a string if possible.
     *
     * @param mixed &$value value to be converted.
     * @return bool whether the value has been converted to a string.
     */
    protected static function toString(mixed &$value): bool
    {
        if (null === $value || is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $value = (string) $value;
            return true;
        }
        return false;
    }

    /**
     * Converts an empty value to null.
     *
     * If passed a non-empty value, do nothing.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be converted.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool always return `true`.
     */
    public static function emptyToNull(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (static::isEmpty($newValue)) {
            $newValue = null;
        }
        return true;
    }

    /**
     * Converts a scalar value to a string and strip whitespace characters from the beginning and end
     * of the string.
     *
     * If passed a non-scalar value, do nothing.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be striped.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool always return `true`.
     */
    public static function trim(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (is_scalar($newValue)) {
            $newValue = trim((string) $newValue);
        }
        return true;
    }

    /**
     * Validates whether the value is not null.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function notNull(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (null === $newValue) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' cannot be blank.',
            );
            return false;
        }
        return true;
    }

    /**
     * Validates whether the value is not empty.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function notEmpty(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (static::isEmpty($newValue)) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' cannot be blank.',
            );
            return false;
        }
        return true;
    }

    /**
     * Validates whether the value is a string.
     *
     * Converts the value to a string if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function isString(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (!static::toString($newValue)) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' must be a string.',
            );
            return false;
        }
        return true;
    }

    /**
     * Validates whether the value is a string or null.
     *
     * Converts a non-null value to a string if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableString(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::isString($attribute, $path, $newValue, $result);
    }

    /**
     * Validates whether the value is an integer.
     *
     * Converts the value to an integer if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param int|string &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function isInt(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        $val = filter_var($newValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if (null === $val) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' must be an integer.',
            );
            return false;
        }
        $newValue = (int) $val;
        return true;
    }

    /**
     * Validates whether the value is an integer or null.
     *
     * Converts a non-null value to an integer if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableInt(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::isInt($attribute, $path, $newValue, $result);
    }

    /**
     * Validates whether the value is a float.
     *
     * Converts the value to a float if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function isFloat(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        $val = filter_var($newValue, FILTER_VALIDATE_FLOAT, [
            'flags' => FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_THOUSAND,
            'options' => ['decimal' => '.'],
        ]) ?? filter_var($newValue, FILTER_VALIDATE_FLOAT, [
            'flags' => FILTER_NULL_ON_FAILURE,
            'options' => ['decimal' => ','],
        ]);
        if (null === $val) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' must be a float.',
            );
            return false;
        }
        $newValue = (float) $val;
        return true;
    }

    /**
     * Validates whether the value is a float or null.
     *
     * Converts a non-null value to a float if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableFloat(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::isFloat($attribute, $path, $newValue, $result);
    }

    /**
     * Validates whether the value is a boolean.
     *
     * Converts the value to a boolean if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function isBool(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        $val = filter_var($newValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if (null === $val) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' must be a boolean.',
            );
            return false;
        }
        $newValue = (bool) $val;
        return true;
    }

    /**
     * Validates whether the value is a boolean or null.
     *
     * Converts a non-null value to a boolean if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableBool(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::isBool($attribute, $path, $newValue, $result);
    }

    /**
     * Validates whether the value is a valid email address.
     *
     * Converts the value to a valid email address if possible.
     *
     * In general, this validates e-mail addresses against the addr-specsyntax in » RFC 822, with the
     * exceptions that comments and whitespace folding and dotless domain names are not supported.
     * (see https://www.php.net/manual/en/filter.filters.validate.php)
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function email(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        $val = filter_var($newValue, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE | FILTER_FLAG_EMAIL_UNICODE);
        if (null === $val) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                $result->fullName($attribute, $path, true) . ' is not a valid email address.',
            );
            return false;
        }
        $newValue = (string) $val;
        return true;
    }

    /**
     * Validates whether the value is a valid email address or null.
     *
     * Converts a non-null value to a valid email address if possible.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableEmail(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::email($attribute, $path, $newValue, $result);
    }

    /**
     * Validates whether the value is an object.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function object(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (!is_object($newValue)) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' is invalid.',
            );
            return false;
        }
        return true;
    }

    /**
     * Validates whether the value is an object or null.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableObject(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::object($attribute, $path, $newValue, $result);
    }

    /**
     * Validates whether the value is a `\DateTimeImmutable`.
     *
     * The passed value may be a timestamp, in which case it will be converted to `\DateTimeImmutable`.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function dateTimeImmutable(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        if (!($newValue instanceof DateTimeImmutable)) {
            $val = filter_var($newValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if (is_int($val)) {
                $newValue = DateTimeImmutable::createFromFormat('U', (string) $val);
            } else {
                $result?->addError(
                    $result::VALIDATION_ERROR,
                    $result->fullName($attribute, $path),
                    'The ' . $result->fullName($attribute, $path, true) . ' must be a timestamp.',
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Validates whether the value is a `\DateTimeImmutable` or null.
     *
     * The passed value may be a timestamp, in which case it will be converted to `\DateTimeImmutable`.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    public static function nullableDateTimeImmutable(string $attribute, string $path, mixed &$newValue, OperationResult|null $result = null): bool
    {
        return (null === $newValue) ?: static::dateTimeImmutable($attribute, $path, $newValue, $result);
    }
}
