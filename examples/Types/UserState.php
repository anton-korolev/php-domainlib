<?php

declare(strict_types=1);

namespace DomainLib\Examples;


use DomainLib\DataUnits\EntityState;
use DomainLib\Results\OperationResult;

use DateTimeImmutable;

/**
 * State of the User entity.
 *
 * See `attributeSpecifications()` for more details on entity rules.
 *
 * @property-read string $id user id
 * @property-read string $login user login
 * @property-read Password $password user password
 * @property-read FullName $fullName full name
 * @property-read PhoneWithDTO|null $phone phone
 * @property-read string|null $email email
 * @property-read bool $active activity
 * @property-read DateTimeImmutable $createdAt creation date
 * @property-read DateTimeImmutable $updatedAt last update date
 */
class UserState extends EntityState
{
    protected const DTO_CLASS = UserStateDTO::class;

    protected string $id;
    protected string $login;
    protected Password $password;
    protected FullName $fullName;
    protected PhoneWithDTO|null $phone;
    protected string|null $email;
    protected bool $active;
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;

    /**
     *
     */
    protected static function attributeSpecifications(): array
    {
        return [
            'id' => [
                'validators' => ['isString', 'trim', 'notEmpty', 'validateId' => static::validateId(...)],
            ],

            'login' => [
                'validators' => ['isString', 'trim', 'notEmpty', 'validateLogin' => static::validateLogin(...)],
            ],

            'password' => [
                'class' => Password::class,
                'default' => [],
                'validators' => ['notNull'],
            ],

            'fullName' => [
                'class' => FullName::class,
                'default' => [],
                'validators' => ['notNull'],
            ],

            'phone' => [
                'class' => PhoneWithDTO::class,
            ],

            'email' => [
                'validators' => ['nullableString', 'trim', 'emptyToNull', 'nullableEmail'],
            ],

            'active' => [
                'validators' => ['isBool'],
            ],

            'createdAt' => [
                'default' => fn (): int => time(),
                // 'default' => fn (): DateTimeImmutable => new DateTimeImmutable(),
                'validators' => ['dateTimeImmutable'],
                'getter' => static::getTimestump(...),
            ],

            'updatedAt' => [
                // 'default' => fn (): DateTimeImmutable => new DateTimeImmutable(),
                'generator' => fn (mixed $newValue): mixed => $newValue ?? time(),
                'validators' => ['dateTimeImmutable'],
                'getter' => static::getTimestump(...),
            ],
        ];
    }

    /**
     * Factory method to create an instance of `UserState`.
     *
     * @return static|null a new instance of `UserState` if the state creation passed without errors,
     * or `null` otherwise.
     */
    public static function create(
        string|null $id,
        string $login,
        Password $password,
        FullName $fullName,
        PhoneWithDTO|null $phone,
        string|null $email,
        bool $active,
        string $recordPath,
        OperationResult $result,
    ): static|null {
        return parent::internalCreate([
            'id' => $id,
            'login' => $login,
            'password' => $password,
            'fullName' => $fullName,
            'phone' => $phone,
            'email' => $email,
            'active' => $active,
        ], $recordPath, $result);
    }

    /**
     * Additional Id validator.
     */
    protected static function validateId(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
    {
        return true;
    }

    /**
     * Additional Login validator.
     */
    protected static function validateLogin(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
    {
        return true;
    }

    /**
     * Returns timestamp from the `DateTimeImmutable`.
     *
     * Getter for attributes with type `DateTimeImmutable`.
     *
     * @param string $attribute a attribute name.
     * @param DateTimeImmutable $value attribute value.
     * @param int $options bitmask of attribute getter options.
     * @return int timestamp.
     */
    protected static function getTimestump(string $attribute, DateTimeImmutable $value, int $options): int
    {
        return $value->getTimestamp();
    }
}
