<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\DataTransferObject;

/**
 */
class PasswordDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $hash,
    ) {
    }

    /*
     * To define a list of attributes, you can override `attributes()` directly, or override
     * `attributeSpecifications()` with empty specifications for all attributes.
     */
    /* delete or append the second-to-last space */
    protected static function attributes(): array
    {
        return [
            'hash',
        ];
    }
    /*/
    protected static function attributeSpecifications(): array
    {
        return [
            'hash',
        ];
    }
/**/
}
