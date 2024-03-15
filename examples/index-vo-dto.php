<?php

declare(strict_types=1);

namespace DomainLib\Examples;

use DomainLib\Results\OperationResult;

/* delete or append the second-to-last space */

// Without Composer autoloader.
require __DIR__ . '/../src/Results/OperationResult.php';
require __DIR__ . '/../src/Validators/BaseValidators.php';
require __DIR__ . '/../src/DataUnits/DataTransferInterface.php';
require __DIR__ . '/../src/DataUnits/AbstractRecord.php';
require __DIR__ . '/../src/DataUnits/DataTransferObject.php';
require __DIR__ . '/../src/DataUnits/ValidRecord.php';
require __DIR__ . '/../src/DataUnits/ValueObject.php';
require __DIR__ . '/../src/DataUnits/EntityState.php';

require __DIR__ . './Types/Phone.php';
require __DIR__ . './Types/PhoneDTO.php';
require __DIR__ . './Types/PhoneWithDTO.php';

/*/

// Use the Composer autoloader.
$loader = require __DIR__ . '/../vendor/autoload.php';

/**/

echo '<pre>' . PHP_EOL;

echo '# 1 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Creating VO Phone with invalid attribute values (\'ree\', \' 0  \', \'123\'):'
    . PHP_EOL;

$phone = Phone::create(
    'ree',
    ' 0  ',
    '123',
    '',
    $result = new OperationResult()
);
echo PHP_EOL . '$phone: ';
var_dump($phone);

echo PHP_EOL . 'Operation result:' . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL
    . '# 2 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'Creating VO Phone with valid attribute values (\'+765\', \'   222   \', \'1234567\'):'
    . PHP_EOL;

$phone = Phone::create(
    '+765',
    '   222   ',
    '1234567',
    '',
    $result = new OperationResult()
);
echo PHP_EOL . '$phone: ';
var_dump($phone);

echo PHP_EOL . 'Operation result:' . PHP_EOL . '$result: ';
var_dump($result);
unset($result);

echo PHP_EOL
    . '# 3 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'The DTO_CLASS constant is not set in the Phone VO class:' . PHP_EOL
    . '$phoneDTO = $phone->toDTO();' . PHP_EOL;
$phoneDTO = $phone->toDTO();

echo PHP_EOL . '$phoneDTO: ';
var_dump($phoneDTO);

echo PHP_EOL
    . '# 4 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo 'The DTO_CLASS constant is set in the PhoneWithDTO VO class:' . PHP_EOL
    . '$phoneDTO = $phoneWithDTO->toDTO();' . PHP_EOL;

$phoneWithDTO = PhoneWithDTO::create(
    '+765',
    '222',
    '1234567',
    '',
    $result = new OperationResult()
);
$phoneDTO = $phoneWithDTO->toDTO();
echo PHP_EOL . '$phoneDTO: ';
var_dump($phoneDTO);

echo PHP_EOL . '$phoneDTO->getAttributes(): ';
var_dump($phoneDTO->getAttributes());

echo PHP_EOL
    . '# 5 ---------------------------------------------------------------------------------------'
    . PHP_EOL;
echo ' Direct create PhoneDTO. Values are not validated and may be set incorrectly:'
    . PHP_EOL . '$phoneDTO = new PhoneDTO(\'+765\', \'   222   \', \'1234567\'):'
    . PHP_EOL;

$phoneDTO = new PhoneDTO('+765', '   222   ', '1234567');
echo PHP_EOL . '$phoneDTO: ';
var_dump($phoneDTO);

echo PHP_EOL . '$phoneDTO->getAttributes(): ';
var_dump($phoneDTO->getAttributes());

echo '</pre>';
