<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\DataTransferObject;

/**
 * @property-read string $country
 * @property-read string $code
 * @property-read string $number
 */
class PhoneDTO extends DataTransferObject
{
    public function __construct(
        protected string $country,
        protected string $code,
        protected string $number,
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
            'country',
            'code',
            'number',
        ];
    }
    /*/
    protected static function attributeSpecifications(): array
    {
        return [
            'country',
            'code',
            'number' => [],
        ];
    }
    /**/
}
