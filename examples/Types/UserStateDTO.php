<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\DataTransferObject;

/**
 */
class UserStateDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $login,
        public readonly PasswordDTO $password,
        public readonly FullNameDTO $fullName,
        public readonly PhoneDTO|null $phone,
        public readonly string|null $email,
        public readonly bool $active,
        public readonly int $createdAt,
        public readonly int $updatedAt,
    ) {
    }

    protected static function attributeSpecifications(): array
    {
        return array_merge(
            array_fill_keys(UserState::attributeList(), []),
            [
                'password' => ['dtoClass' => PasswordDTO::class],
                'fullName' => ['dtoClass' => FullNameDTO::class],
                'phone' => ['dtoClass' => PhoneDTO::class],
            ]
        );

        // return [
        //     'id',
        //     'login',
        //     'password' => ['dtoClass' => PasswordDTO::class],
        //     'fullName' => ['dtoClass' => FullNameDTO::class],
        //     'phone' => ['dtoClass' => PhoneDTO::class],
        //     'email',
        //     'active',
        //     'createdAt',
        //     'updatedAt',
        // ];
    }
}
