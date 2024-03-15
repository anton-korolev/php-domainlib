<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\ValueObject;
use DomainLib\Results\OperationResult;

/**
 * @property-read string $first
 * @property-read string|null $middle
 * @property-read string|null $last
 */
class FullName extends ValueObject
{
    protected const DTO_CLASS = FullNameDTO::class;

    protected string $first;
    protected string|null $middle;
    protected string|null $last;

    protected static function attributeSpecifications(): array
    {
        return [
            'first' => ['validators' => ['isString', 'trim', 'notEmpty']],
            'middle' => ['validators' => ['nullableString', 'trim', 'emptyToNull']],
            'last' => ['validators' => ['nullableString', 'trim', 'emptyToNull']],
        ];
    }

    public static function create(
        string $first,
        ?string $middle,
        ?string $last,
        string $recordPath,
        OperationResult $result,
    ): static|null {
        return parent::internalCreate([
            'first' => $first,
            'middle' => $middle,
            'last' => $last
        ], $recordPath, $result);
    }
}
