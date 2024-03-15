<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\DataTransferObject;

/**
 */
class FullNameDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $first,
        public readonly ?string $middle,
        public readonly ?string $last,
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
            'first',
            'middle',
            'last',
        ];
    }
    /*/
    protected static function attributeSpecifications(): array
    {
        return [
            'first',
            'middle',
            'last' => [],
        ];
    }
/**/
}
