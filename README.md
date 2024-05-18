# PHP DomainLib

PHP library for designing entities and aggregates.

The project is at an early stage of development. The current version is 0.1.29.

## Features

- Automatic creation of nested objects tree.
- Automatically convert an object (including all nested objects) to a DTO or array and back again.
- Validate attributes (properties) when creating or modifying an object (including all nested objects).
- Simple description of the object's attribute specifications. Supports the following specifications:
  - `validators` - list of attribute validators.
  - `default` - default value for an object attribute.
  - `generator` - generator for an object attribute.
  - `getter` - getter for an object attribute.
  - `setter` - setter for an object attribute.
- Available the following data units:
  - Data Transfer Object
  - Partial Data Transfer Object
  - Value Object
  - Entity State
- 100% strict types.

## Requirements

- PHP 8.1 or higher.

## Installation

```shell
composer require domainlib/domainlib --dev
```

## Usage

[Check the examples](./examples/) to learn about usage.

## Data units

### AbstractRecord

Base class for all records.

```php
abstract class AbstractRecord { }
```

Implements the following features:

- Attribute list.
- Attribute specifications.
- Retrieve record attribute values (including nested records) as an associative array (see `internalGetAttributes()`).
- Magic `__get()` method to retrieve values of protected or private attributes.
- Magic `__set()` method to disallow dynamic properties.

### DataTransferObject

Base class for Data Transfer Objects (DTO).

```php
abstract class DataTransferObject extends AbstractRecord { }
```

Implements the following commonly used features:

- Create a DTO from an associative array with attribute type control and automatic creation of all internal DTOs (see `createFromArray()`).
- Convert a DTO (including nested DTOs) to an associative array (see `DTOtoArray()`).

 Supports the following attribute specifications:

 - `dtoClass` - the class name of the nested DTO attribute.

By default, the list of attributes and the list of class names of nested DTOs are extracted from the constructor parameters. This behavior can be changed by overriding the `attributeSpecifications()`, `attributes()` and `dtoClasses()` methods.


Typical example of a DTO class (php 8.1):

```php
class UserDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly PhoneDTO $phone,
    ) {}

    protected static function attributeSpecifications(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone' => ['dtoClass' => 'PhoneDTO'],
        ];
    }
}
```

### PartialDTO

Partial Data Transfer Object.

```php
abstract class PartialDTO extends DataTransferObject { }
```

Additional implements the following features:

- Partially setting DTO attributes and getting only set attributes (see `createFromArray()`).

Note, for partial DTO to work, in `__constructor()` must specify default values for attributes that can be omitted.

Typical example of creating a DTO class (php 8.1):

```php
class UserDTO extends PartialDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string|null $email = null,
        public readonly PhoneDTO|null $phone = null,
    ) {}

    protected static function attributeSpecifications(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone' => ['dtoClass' => 'PhoneDTO'],
        ];
    }
}

$userDTO = UserDTO::createFromArray([
    'id' => 1001,
    'name' => Guest,
]);
```

### DataTransferInterface

```php
interface DataTransferInterface
{
	public static function createFromDTO(array|DataTransferObject $values, string $recordPath, OperationResult $result): static|null;

	public function toDTO(array|null $workAttributes = null): array|DataTransferObject;
}
```

### ValidRecord

Record with attribute validation.

```php
abstract class ValidRecord extends AbstractRecord implements DataTransferInterface { }
```

Additional implements the following features:

- Validate new attribute values (including attributes of nested records) before creating or updating a record (see `validateAttributeValues()`).
- Create a new or update an existing record (including nested records) from an associative array (see `internalCreate()` and `internalSetAttributes()`).
- Create a new record (including nested records) from the DTO (see `createFromDTO()`).
- Convert the record (including nested records) to a DTO (see `toDTO()`).

Supports the following attribute specifications:

- `class` - the attribute's class name.
- `validators` - list of attribute validators.

### ValueObject

Base class for Value Objects (VO).

```php
abstract class ValueObject extends ValidRecord { }
```

Additional implements the following features:
- When creating a new ValueObject from an associative array, missing attribute values will be filled with `null` (see `internalCreate()`).

Typical excample usage ValueObject class (php 8.1):

```php
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
            'first' => ['validators' => ['string', 'trim', 'notEmpty']],
            'middle' => ['validators' => ['nullableString', 'trim', 'emptyToNull']],
            'last' => ['validators' => ['nullableString', 'trim', 'emptyToNull']],
        ];
    }

    public static function create(
        string $first,
        string|null $middle,
        string|null $last,
        string $recordPath,
        OperationResult $result,
    ): static|null {
        return parent::internalCreate([
            'first' => $first,
            'middle' => $middle,
            'last' => $last
        ], $recordPath, $result);
    }

    // You can override the `toDTO()` function to cast the return value to the correct DTO class.
    public function toDTO(array|null $attributes = null): FullNameDTO
    {
        return parent::toDTO($attributes);
    }
}
```

### EntityState

Base class for entity states.

```php
abstract class EntityState extends ValidRecord { }
```

Additional implements the following features:

- When creating a new EntityState from an associative array, the missing attribute values will be filled in from the default values (see `attributeDefaults()`).

Supports the following additional attribute specifications:

- `default` - default value for an entity attribute. Can be a simple value, callable or the name of a static method of the current class, in the latter cases it will be computed at runtime (see `attributeDefaults()`).
- `getter` - getter for an entity attribute (see `attributeGetters()`). Applies only to the massive way of getting attribute values using `internalGetAttributes()`.
- `setter` - setter for an entity attribute (see `attributeSetters()`). Applies only to the massive way of setting attribute values using `internalSetAttributes()`.
- `generator` - generator for an entity attribute (see `attributeGenerators()`). Applies only to the massive way of setting attribute values using `internalSetAttributes()`. Unlike a setter, the generator will be called anyway, even if no new attribute value is passed in.

Typical excample usage EntityState class (php 8.1):

```php
/**
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

    // Factory method to create an instance of `UserState`.
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

    // Additional Id validator.
    protected static function validateId(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
    {
        return true;
    }

    // Additional Login validator.
    protected static function validateLogin(string $attribute, string $path, mixed &$newValue, OperationResult $result): bool
    {
        return true;
    }

    // Getter. Returns timestamp from the `DateTimeImmutable`.
    protected static function getTimestump(string $attribute, DateTimeImmutable $value, int $options): int
    {
        return $value->getTimestamp();
    }
}
```

## License

The DomainLib is free software. It is released under the terms of the BSD License. Please see [`LICENSE`](./LICENSE.txt) for more information.

Maintained by Anton Korolev ([GitLab](https://gitlab.com/anton-korolev), [GitHub](https://github.com/anton-korolev))
