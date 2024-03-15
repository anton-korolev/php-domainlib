# Usage examples.

- [index-entity-state.php](./index-entity-state.php) - examples of using [EntityState](../src/DataUnits/EntityState.php).
- [index-vo-dto.php](./index-vo-dto.php) - examples of using [ValueObject](../src/DataUnits/ValueObject.php) and [DataTransferObject](../src/DataUnits/DataTransferObject.php).

## Output of index-entity-state.php

```
# 1 ---------------------------------------------------------------------------------------
Retriving EntityState specification.

UserState::attributeClasses():
array(3) {
  ["password"]=>
  string(27) "DomainLib\Examples\Password"
  ["fullName"]=>
  string(27) "DomainLib\Examples\FullName"
  ["phone"]=>
  string(31) "DomainLib\Examples\PhoneWithDTO"
}

UserState::attributeDefaults():
array(9) {
  ["id"]=>
  NULL
  ["login"]=>
  NULL
  ["password"]=>
  array(0) {
  }
  ["fullName"]=>
  array(0) {
  }
  ["phone"]=>
  NULL
  ["email"]=>
  NULL
  ["active"]=>
  NULL
  ["createdAt"]=>
  int(1710503113)
  ["updatedAt"]=>
  NULL
}

# 2 ---------------------------------------------------------------------------------------
Creating `UserState` from an array with invalid attribute values:

$userState: NULL

Operation result:
$result: object(DomainLib\Results\OperationResult)#8 (2) {
  ["hasErrors":protected]=>
  bool(true)
  ["errors":protected]=>
  array(1) {
    [3]=>
    array(3) {
      ["password"]=>
      array(1) {
        [0]=>
        string(31) "The {password} type is invalid."
      }
      ["fullName\first"]=>
      array(1) {
        [0]=>
        string(37) "The {fullName\first} cannot be blank."
      }
      ["id"]=>
      array(1) {
        [0]=>
        string(25) "The {id} cannot be blank."
      }
    }
  }
}

# 3 ---------------------------------------------------------------------------------------
Creating `UserState` from an array with valid attribute values:

$userState: object(DomainLib\Examples\UserState)#29 (10) {
  ["recordPath":protected]=>
  string(0) ""
  ["id":protected]=>
  string(23) "65f434c97087a8.40520793"
  ["login":protected]=>
  string(5) "Guest"
  ["password":protected]=>
  object(DomainLib\Examples\Password)#7 (2) {
    ["recordPath":protected]=>
    string(8) "password"
    ["hash":protected]=>
    string(97) "$argon2id$v=19$m=65536,t=4,p=1$U083SllRQnR5UGU1eXVELw$IqMbpW8TH8wINERmofqaVHBvOvwPMzo0Z/TpHZgoTuM"
  }
  ["fullName":protected]=>
  object(DomainLib\Examples\FullName)#30 (4) {
    ["recordPath":protected]=>
    string(8) "fullName"
    ["first":protected]=>
    string(4) "Ivan"
    ["middle":protected]=>
    NULL
    ["last":protected]=>
    string(6) "Ivanov"
  }
  ["phone":protected]=>
  NULL
  ["email":protected]=>
  NULL
  ["active":protected]=>
  bool(false)
  ["createdAt":protected]=>
  object(DateTimeImmutable)#28 (3) {
    ["date"]=>
    string(26) "2024-03-15 11:45:13.000000"
    ["timezone_type"]=>
    int(1)
    ["timezone"]=>
    string(6) "+00:00"
  }
  ["updatedAt":protected]=>
  object(DateTimeImmutable)#27 (3) {
    ["date"]=>
    string(26) "2024-03-15 11:45:13.000000"
    ["timezone_type"]=>
    int(1)
    ["timezone"]=>
    string(6) "+00:00"
  }
}

Operation result:
$result: object(DomainLib\Results\OperationResult)#8 (2) {
  ["hasErrors":protected]=>
  bool(false)
  ["errors":protected]=>
  array(0) {
  }
}

# 4 ---------------------------------------------------------------------------------------
Converting `UserState` to DTO (using the toDTO() method):
$userStateDTO = $userState->toDTO();

$userStateDTO: object(DomainLib\Examples\UserStateDTO)#31 (9) {
  ["id"]=>
  string(23) "65f434c97087a8.40520793"
  ["login"]=>
  string(5) "Guest"
  ["password"]=>
  object(DomainLib\Examples\PasswordDTO)#34 (1) {
    ["hash"]=>
    string(97) "$argon2id$v=19$m=65536,t=4,p=1$U083SllRQnR5UGU1eXVELw$IqMbpW8TH8wINERmofqaVHBvOvwPMzo0Z/TpHZgoTuM"
  }
  ["fullName"]=>
  object(DomainLib\Examples\FullNameDTO)#32 (3) {
    ["first"]=>
    string(4) "Ivan"
    ["middle"]=>
    NULL
    ["last"]=>
    string(6) "Ivanov"
  }
  ["phone"]=>
  NULL
  ["email"]=>
  NULL
  ["active"]=>
  bool(false)
  ["createdAt"]=>
  int(1710503113)
  ["updatedAt"]=>
  int(1710503113)
}

# 5 ---------------------------------------------------------------------------------------
Creating `UserState` from DTO with valid attribute values:

$userState: object(DomainLib\Examples\UserState)#36 (10) {
  ["recordPath":protected]=>
  string(0) ""
  ["id":protected]=>
  string(23) "65f434c97087a8.40520793"
  ["login":protected]=>
  string(5) "Guest"
  ["password":protected]=>
  object(DomainLib\Examples\Password)#37 (2) {
    ["recordPath":protected]=>
    string(8) "password"
    ["hash":protected]=>
    string(97) "$argon2id$v=19$m=65536,t=4,p=1$U083SllRQnR5UGU1eXVELw$IqMbpW8TH8wINERmofqaVHBvOvwPMzo0Z/TpHZgoTuM"
  }
  ["fullName":protected]=>
  object(DomainLib\Examples\FullName)#38 (4) {
    ["recordPath":protected]=>
    string(8) "fullName"
    ["first":protected]=>
    string(4) "Ivan"
    ["middle":protected]=>
    NULL
    ["last":protected]=>
    string(6) "Ivanov"
  }
  ["phone":protected]=>
  NULL
  ["email":protected]=>
  NULL
  ["active":protected]=>
  bool(false)
  ["createdAt":protected]=>
  object(DateTimeImmutable)#39 (3) {
    ["date"]=>
    string(26) "2024-03-15 11:45:13.000000"
    ["timezone_type"]=>
    int(1)
    ["timezone"]=>
    string(6) "+00:00"
  }
  ["updatedAt":protected]=>
  object(DateTimeImmutable)#40 (3) {
    ["date"]=>
    string(26) "2024-03-15 11:45:13.000000"
    ["timezone_type"]=>
    int(1)
    ["timezone"]=>
    string(6) "+00:00"
  }
}

Operation result:
$result: object(DomainLib\Results\OperationResult)#8 (2) {
  ["hasErrors":protected]=>
  bool(false)
  ["errors":protected]=>
  array(0) {
  }
}

# 6 ---------------------------------------------------------------------------------------
Change attributes (using setAttributes() method):

Set correct attributes: bool(true)

Operation result:
$result: object(DomainLib\Results\OperationResult)#8 (2) {
  ["hasErrors":protected]=>
  bool(false)
  ["errors":protected]=>
  array(0) {
  }
}

Set invalid attributes: bool(false)

Operation result:
$result: object(DomainLib\Results\OperationResult)#8 (2) {
  ["hasErrors":protected]=>
  bool(true)
  ["errors":protected]=>
  array(1) {
    [3]=>
    array(1) {
      ["updatedAt"]=>
      array(1) {
        [0]=>
        string(36) "The {updatedAt} must be a timestamp."
      }
    }
  }
}

# 7 ---------------------------------------------------------------------------------------
Password verification:

Correct password 'Guest password' using the isEqual() method: bool(true)
Invalid password 'password' using the isEqual() method: bool(false)

Correct password 'Guest password' using the static method Password::verify(): bool(true)
Invalid password 'password' using the static method Password::verify(): bool(false)
```

## Output of index-vo-dto.php

```
# 1 ---------------------------------------------------------------------------------------
Creating VO Phone with invalid attribute values ('ree', ' 0  ', '123'):

$phone: NULL

Operation result:
$result: object(DomainLib\Results\OperationResult)#4 (2) {
  ["hasErrors":protected]=>
  bool(true)
  ["errors":protected]=>
  array(1) {
    [3]=>
    array(3) {
      ["country"]=>
      array(1) {
        [0]=>
        string(18) "Invalid {country}."
      }
      ["code"]=>
      array(1) {
        [0]=>
        string(27) "The {code} cannot be blank."
      }
      ["number"]=>
      array(1) {
        [0]=>
        string(17) "Invalid {number}."
      }
    }
  }
}

# 2 ---------------------------------------------------------------------------------------
Creating VO Phone with valid attribute values ('+765', '   222   ', '1234567'):

$phone: object(DomainLib\Examples\Phone)#6 (4) {
  ["recordPath":protected]=>
  string(0) ""
  ["country":protected]=>
  string(4) "+765"
  ["code":protected]=>
  string(3) "222"
  ["number":protected]=>
  string(7) "1234567"
}

Operation result:
$result: object(DomainLib\Results\OperationResult)#4 (2) {
  ["hasErrors":protected]=>
  bool(false)
  ["errors":protected]=>
  array(0) {
  }
}

# 3 ---------------------------------------------------------------------------------------
The DTO_CLASS constant is not set in the Phone VO class:
$phoneDTO = $phone->toDTO();

$phoneDTO: array(3) {
  ["country"]=>
  string(4) "+765"
  ["code"]=>
  string(3) "222"
  ["number"]=>
  string(7) "1234567"
}

# 4 ---------------------------------------------------------------------------------------
The DTO_CLASS constant is set in the PhoneWithDTO VO class:
$phoneDTO = $phoneWithDTO->toDTO();

$phoneDTO: object(DomainLib\Examples\PhoneDTO)#30 (3) {
  ["country":protected]=>
  string(4) "+765"
  ["code":protected]=>
  string(3) "222"
  ["number":protected]=>
  string(7) "1234567"
}

$phoneDTO->getAttributes(): array(3) {
  ["country"]=>
  string(4) "+765"
  ["code"]=>
  string(3) "222"
  ["number"]=>
  string(7) "1234567"
}

# 5 ---------------------------------------------------------------------------------------
 Direct create PhoneDTO. Values are not validated and may be set incorrectly:
$phoneDTO = new PhoneDTO('+765', '   222   ', '1234567'):

$phoneDTO: object(DomainLib\Examples\PhoneDTO)#31 (3) {
  ["country":protected]=>
  string(4) "+765"
  ["code":protected]=>
  string(9) "   222   "
  ["number":protected]=>
  string(7) "1234567"
}

$phoneDTO->getAttributes(): array(3) {
  ["country"]=>
  string(4) "+765"
  ["code"]=>
  string(9) "   222   "
  ["number"]=>
  string(7) "1234567"
}
```
