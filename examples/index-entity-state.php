<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\Results\OperationResult;

/* delete or append the second-to-last space * /

// Without Composer autoloader.
require __DIR__ . '/../src/Results/OperationResult.php';
require __DIR__ . '/../src/Validators/BaseValidators.php';
require __DIR__ . '/../src/DataUnits/DataTransferInterface.php';
require __DIR__ . '/../src/DataUnits/AbstractRecord.php';
require __DIR__ . '/../src/DataUnits/DataTransferObject.php';
require __DIR__ . '/../src/DataUnits/ValidRecord.php';
require __DIR__ . '/../src/DataUnits/ValueObject.php';
require __DIR__ . '/../src/DataUnits/EntityState.php';

require __DIR__ . '/../examples/Password.php';
require __DIR__ . '/../examples/PasswordDTO.php';
require __DIR__ . '/../examples/FullName.php';
require __DIR__ . '/../examples/FullNameDTO.php';
require __DIR__ . '/../examples/Phone.php';
require __DIR__ . '/../examples/PhoneDTO.php';
require __DIR__ . '/../examples/PhoneWithDTO.php';
require __DIR__ . '/../examples/UserState.php';
require __DIR__ . '/../examples/UserStateDTO.php';

/*/

// Use the Composer autoloader.
$loader = require __DIR__ . '/../vendor/autoload.php';

/**/

echo '<pre>' . PHP_EOL;

echo '# 1 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Retriving EntityState specification.' . PHP_EOL;

echo PHP_EOL . 'UserState::attributeClasses():' . PHP_EOL;
var_dump(UserState::attributeClasses());

echo PHP_EOL . 'UserState::attributeDefaults():' . PHP_EOL;
var_dump(UserState::attributeDefaults());

echo PHP_EOL
    . '# 2 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Creating `UserState` from an array with invalid attribute values:'
    . PHP_EOL;

$userState = UserState::createFromDTO([
    'id' => null,
    'login' => 'Guest',
    'password' => 'Guest',
    // 'phone' => [],
], '', $result = new OperationResult());
echo PHP_EOL . '$userState: ';
var_dump($userState);

echo PHP_EOL . 'Operation result:' . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL
    . '# 3 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Creating `UserState` from an array with valid attribute values:'
    . PHP_EOL;

$result = new OperationResult();
$userState = UserState::createFromDTO([
    'id' => uniqid('', true),
    'login' => 'Guest',
    'password' => Password::createNew('Guest password', 'password', $result) ?? [],
    // 'password' => ['hash' => '   $argon2id$v=19$m=65536,t=4,p=1$TnZPLnZXQ29JR3hpWlY5dA$gfzfUMUIJ3/EmKjEb7s5H7NYaE/UPeWv6sWRbn3oc54        '],
    'fullName' => ['first' => 'Ivan', 'last' => 'Ivanov'],
    'email' => '',
], '', $result);
echo PHP_EOL . '$userState: ';
var_dump($userState);

echo PHP_EOL . 'Operation result:'
    . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL
    . '# 4 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Converting `UserState` to DTO (using the toDTO() method):'
    . PHP_EOL . '$userStateDTO = $userState->toDTO();'
    . PHP_EOL;

$userStateDTO = $userState->toDTO();
echo PHP_EOL . '$userStateDTO: ';
var_dump($userStateDTO);

echo PHP_EOL
    . '# 5 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Creating `UserState` from DTO with valid attribute values:'
    . PHP_EOL;

$result = new OperationResult();
$userState = UserState::createFromDTO($userStateDTO, '', $result);
echo PHP_EOL . '$userState: ';
var_dump($userState);

echo PHP_EOL . 'Operation result:'
    . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL
    . '# 6 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Change attributes (using setAttributes() method): '
    . PHP_EOL;

echo PHP_EOL . 'Set correct attributes: ';
var_dump($userState->setAttributes(
    ['updatedAt' => time() + 125],
    $result = new OperationResult
));

echo PHP_EOL . 'Operation result:'
    . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL . 'Set invalid attributes: ';
var_dump($userState->setAttributes(
    ['updatedAt' => 'time'],
    $result = new OperationResult
));

echo PHP_EOL . 'Operation result:'
    . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL
    . '# 7 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Password verification:'
    . PHP_EOL;

echo PHP_EOL . 'Correct password \'Guest password\' using the isEqual() method: ';
var_dump(
    $userState->password->isEqual('Guest password')
);
echo 'Invalid password \'password\' using the isEqual() method: ';
var_dump(
    $userState->password->isEqual('password')
);

echo PHP_EOL . 'Correct password \'Guest password\' using the static method Password::verify(): ';
var_dump(
    Password::verify('Guest password', $userState->password->hash)
);
echo 'Invalid password \'password\' using the static method Password::verify(): ';
var_dump(
    Password::verify('password', $userState->password->hash)
);

echo '</pre>';
