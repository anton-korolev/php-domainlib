<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

/**
 * Partial Data Transfer Object.
 *
 * Additional implements the following features:
 * - Partially setting DTO attributes and getting only set attributes (see `createFromArray()`).
 *
 * Note, for partial DTO to work, in `__constructor()` must specify default values for attributes that
 * can be omitted.
 *
 * Typical example of creating a DTO class (php 8.1):
 * ```php
 * class UserDTO extends PartialDTO
 * {
 *     public function __construct(
 *         public readonly string $id,
 *         public readonly string $name,
 *         public readonly string|null $email = null,
 *         public readonly PhoneDTO|null $phone = null,
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
 *
 * $userDTO = UserDTO::createFromArray([
 *     'id' => 1001,
 *     'name' => Guest,
 * ]);
 * ```
 *
 * @see DataTransferObject
 */
abstract class PartialDTO extends DataTransferObject
{
    /**
     * @var array<int,string>|null $workAttributes list of DTO attribute names that have been set.
     * Defaults to null, meaning all DTO attributes have been set. This allows you to not set
     * $workAttributes in the constructor and create DTOs with a simple "new" statement.
     */
    private array|null $workAttributes = null;

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     *
     * You can only set part of the DTO attributes: if the passed array contains only part of the
     * DTO attributes, then those attributes will be marked as work and only their values will be
     * returned by the `getAttributes()`.
     * Note, you must specify default attribute values directly in `__construct()` to do this.
     *
     * @return static new instance of a PartialDTO.
     */
    public static function createFromArray(array $values): static
    {
        $newInstance = parent::createFromArray($values);
        $newInstance->workAttributes =
            array_intersect(array_keys($values), static::attributeList());
        return $newInstance;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     *
     * Only work attribute values can be returned.
     *
     * Work attributes are the attributes that were set when the DTO was created via `createFromArray()`.
     *
     */
    protected function internalGetAttributes(array|null $attributes = null, int|null $options = null): array
    {
        $attributes = (null === $attributes)
            ? $this->workAttributes
            : ((null === $this->workAttributes)
                ? $attributes
                : array_intersect($attributes, $this->workAttributes)
            );

        return parent::internalGetAttributes($attributes, $options);
    }

    /**
     * {@inheritdoc}
     *
     * Only work attributes are considered valid. Work attributes are the attributes that were set when
     * the DTO was created via `createFromArray()`.
     *
     */
    protected function isAttributeValid(string $attribute, string|null &$message): bool
    {
        $message = null;

        if (!parent::isAttributeValid($attribute, $message)) {
            return false;
        } elseif (
            (null === $this->workAttributes)
            || in_array($attribute, $this->workAttributes, true)
        ) {
            return true;
        } else {
            $message = 'Access to unset attribute in partial-set mode is denied: '
                . $this::class . "::$attribute";
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     *
     * Only work attribute values can be returned.
     *
     * Work attributes are the attributes that were set when the DTO was created via `createFromArray()`.
     *
     */
    public function getAttributes(array|null $attributes = null): array
    {
        return parent::getAttributes($attributes);
    }
}
