<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\ValueObject;
use DomainLib\Results\OperationResult;

/**
 * @property-read string $country
 * @property-read string $code
 * @property-read string $number
 */
class Phone extends ValueObject
{
    protected string $country;
    protected string $code;
    protected string $number;

    protected static function attributeSpecifications(): array
    {
        return [
            'country' => [
                'validators' => [
                    'isString', 'trim', 'notEmpty',
                    'validateCountry' => static::validateCountry(...)
                ]
            ],

            'code' => [
                'validators' => [
                    'isString', 'trim', 'notEmpty',
                    'validateCode' => static::validateCode(...)
                ]
            ],

            'number' => [
                'validators' => [
                    'isString', 'trim', 'notEmpty',
                    'validateNumber' => static::validateNumber(...)
                ]
            ],
        ];
    }

    public static function create(
        string $country,
        string $code,
        string $number,
        string $recordPath,
        OperationResult $result,
    ): static|null {
        return parent::internalCreate([
            'country' => $country,
            'code' => $code,
            'number' => $number
        ], $recordPath, $result);
    }

    protected static function validateCountry(string $attribute, string $path, string &$newValue, OperationResult $result): bool
    {
        if (!preg_match('/^\+\d{1,3}$/', $newValue)) {
            $result->addError(
                OperationResult::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'Invalid ' . $result->fullName($attribute, $path, true) . '.'
            );
            return false;
        }

        return true;
    }

    protected static function validateCode(string $attribute, string $path, string &$newValue, OperationResult $result): bool
    {
        if (!preg_match('/^\d{3}$/', $newValue)) {
            $result->addError(
                OperationResult::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'Invalid ' . $result->fullName($attribute, $path, true) . '.'
            );
            return false;
        }

        return true;
    }

    protected static function validateNumber(string $attribute, string $path, string &$newValue, OperationResult $result): bool
    {
        if (!preg_match('/^\d{7,8}$/', $newValue)) {
            $result->addError(
                OperationResult::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'Invalid ' . $result->fullName($attribute, $path, true) . '.'
            );
            return false;
        }

        return true;
    }
}
