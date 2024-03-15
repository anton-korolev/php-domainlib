<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\DataUnits\ValueObject;
use DomainLib\Results\OperationResult;
use DomainLib\Validators\BaseValidators;

/**
 * @property-read string $hash
 */
class Password extends ValueObject
{
    protected const DTO_CLASS = PasswordDTO::class;

    protected string $hash;

    /**
     * {@inheritdoc}
     */
    protected static function attributeSpecifications(): array
    {
        return [
            'hash' => ['validators' => ['isString', 'trim', 'notEmpty']],
        ];
    }

    /**
     * Factory method to create an instance of a `Password` value object from a password string.
     *
     * @param mixed $password password. If `null` or empty string is passed, a random password will
     * be generated.
     * @param string $recordPath path to the `Password` object to be created.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return static|null a new instance of `Password` if the object creation passed without errors,
     * or null otherwise.
     */
    public static function createNew(mixed $password, string $recordPath, OperationResult $result): static|null
    {
        if (
            (null !== $password)
            && !static::validatePassword('password', $recordPath, $password, $result)
        ) {
            return null;
        }

        return static::loadHash(static::passwordHash($password), $recordPath, $result);
    }

    /**
     * Factory method to create an instance of a `Password` value object from a hash string.
     *
     * @param mixed $hash password hash.
     * @param string $recordPath path to the `Password` object to be created.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return static|null a new instance of `Password` if the object creation passed without errors,
     * or null otherwise.
     */
    public static function loadHash(mixed $hash, string $recordPath, OperationResult $result): static|null
    {
        return static::internalCreate([
            'hash' => $hash,
        ], $recordPath, $result);
    }

    /**
     * Checks the password value.
     *
     * Checks password length, valid characters, etc.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param string &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    protected static function validatePasswordValue(string $attribute, string $path, string &$newValue, OperationResult|null $result = null): bool
    {
        $len = strlen($newValue);
        if ($len < 8 || $len > 32) {
            $result?->addError(
                $result::VALIDATION_ERROR,
                $result->fullName($attribute, $path),
                'The ' . $result->fullName($attribute, $path, true) . ' must be from 8 to 32 characters.',
            );
            return false;
        }
        return true;
    }

    /**
     * Validates the password.
     *
     * @param string $attribute attribute name.
     * @param string $path path to the attribute.
     * @param mixed &$newValue value to be validated.
     * @param OperationResult $result storage of operation results (error code, error texts, etc.).
     * @return bool whether the validation of the values passed without errors.
     */
    protected static function validatePassword(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
    {
        // 'password' => ['validators' => ['isString', 'trim', 'validatePasswordValue']]
        return
            BaseValidators::isString($attribute, $path, $newValue, $result)
            && BaseValidators::trim($attribute, $path, $newValue, $result)
            && static::validatePasswordValue($attribute, $path, $newValue, $result);
    }

    /**
     * Generates a random password.
     *
     * @return string new random password.
     */
    protected static function randomPassword(): string
    {
        return random_bytes(16);
    }

    /**
     * Returns a peppered password.
     *
     * Note, if you change `$pepper`, all previously stored hashes will become invalid and you will no
     * longer be able to verify them.
     *
     * @param string $password password.
     * @return string a peppered password.
     */
    private static function pepperedPassword(string $password): string
    {
        $pepper = 'fd+glk?erjgw;j-a3;hj*ld4o0@17%5tjsd#flkb5~9yt4w0y=9t';
        return hash_hmac("sha256", $password, $pepper);
    }

    /**
     * Returns a hash of the peppered password.
     *
     * @param string|null $password hashable password. If `null` or empty string is passed, a random
     * password will be generated.
     * @return string a hash of the peppered password.
     */
    protected static function passwordHash(string|null $password): string
    {
        return password_hash(
            self::pepperedPassword(!empty($password) ? $password : static::randomPassword()),
            PASSWORD_ARGON2ID
        );
    }

    /**
     * Verifies that the password matches the hash.
     *
     * @param mixed $password verifiable password.
     * @param string|null $hash previously created hash.
     * @return bool `true` if the password and hash are not empty and match, or `false` otherwise.
     *
     * @see verify()
     * @see isEqual()
     */
    protected static function passwordVerify(string|null $password, string|null $hash): bool
    {
        return
            !empty($password)
            && !empty($hash)
            && password_verify(self::pepperedPassword($password), $hash);
    }

    /**
     * Converts to a string and trims the verifiable password, if possible.
     *
     * @param mixed $password verifiable password.
     * @return string|null converted to a string and trimmed verifiable password, if possible, or `null`
     * otherwise.
     */
    protected static function verifiablePasswordFilter(mixed $password): string|null
    {
        return
            BaseValidators::isString('password', '', $password)
            ? trim($password)
            : null;
    }

    /**
     * Static helper to verify that the password matches the hash.
     *
     * It is safe against timing attacks. If `$password` or `$hash` is incorrect, a simulated verification
     * will be performed and `false` will be returned.
     *
     * Verifiable password will be converted to a string, if possible, and trimmed.
     *
     * You can safely use this method after searching for a user in the repository, regardless of whether
     * the user was found:
     * ```php
     * $user = findUser($id);
     * if (Password::verify($_POST['password'] ?? null, $user?->passwordHash)) {
     * ...
     * }
     * ```
     *
     * @param mixed $password verifiable password.
     * @param string|null $hash previously created hash.
     * @return bool `true` if the password and hash are correct and match, or `false` otherwise.
     */
    public static function verify(mixed $password, string|null $hash): bool
    {
        $password = static::verifiablePasswordFilter($password);

        if (empty($password) || empty($hash)) {
            // Hash for the peppered password "Correct user password"
            $hash = '$argon2id$v=19$m=65536,t=4,p=1$VlpnNkNOWHg2M1ZjMkh0Uw$toe55HtnfudzsoYnTm77khvQ4C/keKkUXYbGP+ZzKkk';
            $password = 'Wrong user password';
        }

        return static::passwordVerify($password, $hash);
    }

    /**
     * Verifies that the password matches the current hash.
     *
     * Verifiable password will be converted to a string, if possible, and trimmed.
     *
     * Warning, using this method may not be safe against timing attacks. If it is important, use the
     * `verify()` method.
     *
     * @param mixed $password verifiable password.
     * @return bool `true` if the password is correct and matches the current hash, or `false` otherwise.
     *
     * @see verify()
     */
    public function isEqual(mixed $password): bool
    {
        $password = static::verifiablePasswordFilter($password);

        return static::passwordVerify($password, $this->hash);
    }
}
