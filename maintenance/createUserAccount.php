<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\Operand;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;

require_once 'MaintenanceBootstrap.php';

$getopt = new GetOpt(null, [GetOpt::SETTING_STRICT_OPERANDS => true]);
$getopt->addOperand(Operand::create('username', Operand::REQUIRED)->setDescription("Desired username. Must be unique and not a protected username"));
$getopt->addOperand(Operand::create('password', Operand::REQUIRED)->setDescription("User's password"));
$getopt->addOperand(Operand::create('email', Operand::REQUIRED)->setDescription("User's email"));

// process arguments and catch user errors
try {
    $getopt->process();
    Database::make();
    $newUser = User::registerAccount($getopt->getOperand("username"), $getopt->getOperand("password"), $getopt->getOperand("email"));
    echo "User registered!" . PHP_EOL;
    echo "User ID:  {$newUser->idUser}" . PHP_EOL;
    echo "Username: {$newUser->username}" . PHP_EOL;
    echo "Email:    {$newUser->email}" . PHP_EOL;
    echo "RegTime:  {$newUser->created->toDayDateTimeString()}" . PHP_EOL;
} catch (\Throwable $exception) {
    file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
    echo PHP_EOL . $getopt->getHelpText();
}
