<?php

declare(strict_types=1);

namespace DomainLib\Examples;

/**
 * @ method PhoneDTO toDTO(array|null $attributes = null)
 */
class PhoneWithDTO extends Phone
{
    protected const DTO_CLASS = PhoneDTO::class;

    /**
     * {@inheritdoc}
     *
     * You can override the `toDTO()` method to cast the return value to the correct DTO class.
     *
     * @return PhoneDTO converted `Phone` to a `PhoneDTO` instance.
     */
    public function toDTO(array|null $attributes = null): PhoneDTO
    {
        return parent::toDTO($attributes);
    }
}
