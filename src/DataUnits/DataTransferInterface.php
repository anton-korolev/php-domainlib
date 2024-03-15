<?php

declare(strict_types=1);

namespace DomainLib\DataUnits;

use DomainLib\Results\OperationResult;

// interface AttributeInterface
interface DataTransferInterface
{
	public static function createFromDTO(array|DataTransferObject $values, string $recordPath, OperationResult $result): static|null;

	public function toDTO(array|null $workAttributes = null): array|DataTransferObject;
}
