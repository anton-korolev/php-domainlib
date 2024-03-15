<?php

declare(strict_types=1);

namespace DomainLib\Results;

/**
 *
 */
class OperationResult
{
	public const UNDEFINED_ERROR = -1;
	public const SUCCESS = 0;
	public const INPUT_DATA_ERROR = 1;
	public const ACCESS_DENIED = 2;
	public const VALIDATION_ERROR = 3;
	public const NOT_FOUND = 4;
	public const ALREADY_EXISTS = 5;

	/**
	 * A delimiter for the full name (see `fullName()`).
	 */
	public const DELIMITER = '\\';

	/**
	 * A wrapper for the full name (see `fullName()`).
	 */
	protected const ATTRIBUTE_FULL_NAME_WRAPPER = [false => ['', ''], true => ['{', '}']];

	// protected int $errorCode = self::SUCCESS;
	protected bool $hasErrors = false;

	/**
	 * @var array<int,array<string,array<int,string>>> $errors list of errors
	 * (errorCode => [errorKey => [errors]]).
	 **/
	protected array $errors = [];

	/**
	 *
	 */
	public function isSuccess(): bool
	{
		return !$this->hasErrors;
	}

	/**
	 *
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Use attribute names as error keys for validation errors on their values, or the special root key
	 * `static::DELIMITER` for general errors.
	 *
	 */
	public function addError(int $errorCode, string $key, string $error): void
	{
		if ('' === $error) {
			return;
		}

		if ('' === $key) {
			$key = static::DELIMITER;
		}

		$this->hasErrors = $this->hasErrors || (self::SUCCESS !== $errorCode);

		$this->errors[$errorCode][$key][] = $error;
	}

	/**
	 * Returns the full name of the element (including the path).
	 *
	 * @param string $name element name.
	 * @param bool $addWrapper whether or not to add a wrapper to the full path (default is `false`).
	 * @return string a full name (including the path).
	 */
	public static function fullName(string $name, string $path, bool $addWrapper = false): string
	{
		return static::ATTRIBUTE_FULL_NAME_WRAPPER[$addWrapper][0]
			. (('' === $path) ? '' : $path . OperationResult::DELIMITER)
			. $name
			. static::ATTRIBUTE_FULL_NAME_WRAPPER[$addWrapper][1];
	}
}
