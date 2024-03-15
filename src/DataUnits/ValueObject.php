<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

use DomainLib\Results\OperationResult;

/**
 * Base class for Value Objects (VO).
 *
 * Additional implements the following features:
 * - When creating a new ValueObject from an associative array, missing attribute values will be filled
 * with `null` (see `internalCreate()`).
 *
 * Typical excample usage ValueObject class (php 8.1):
 * ```php
 * class FullName extends ValueObject
 * {
 *     protected const DTO_CLASS = FullNameDTO::class;
 *
 *     protected string $first;
 *     protected string|null $middle;
 *     protected string|null $last;
 *
 *     protected static function attributeSpecifications(): array
 *     {
 *         return [
 *             'first' => ['validators' => ['string', 'trim', 'notEmpty']],
 *             'middle' => ['validators' => ['nullableString', 'trim', 'emptyToNull']],
 *             'last' => ['validators' => ['nullableString', 'trim', 'emptyToNull']],
 *         ];
 *     }
 *
 *     // Factory method to create an instance of `FullName`.
 *     public static function create(
 *         string $first,
 *         string|null $middle,
 *         string|null $last,
 *         string $recordPath,
 *         OperationResult $result,
 *     ): static|null {
 *         return parent::internalCreate([
 *             'first' => $first,
 *             'middle' => $middle,
 *             'last' => $last
 *         ], $recordPath, $result);
 *     }
 *
 *     // You can override the `toDTO()` method to cast the return value to the correct DTO class.
 *     public function toDTO(array|null $attributes = null): FullNameDTO
 *     {
 *         return parent::toDTO($attributes);
 *     }
 * }
 * ```
 *
 */
abstract class ValueObject extends ValidRecord
{
    /**
     * Internal factory method to create an instance of `ValueObject` from an associative array
     * (attribute => value).
     *
     * Missing attribute values will be filled with `null`.
     *
     * {@inheritdoc}
     */
    protected static function internalCreate(array $values, string $recordPath, OperationResult $result): static|null
    {
        $values = array_merge(array_fill_keys(static::attributes(), null), $values);
        return parent::internalCreate($values, $recordPath, $result);
    }
}
