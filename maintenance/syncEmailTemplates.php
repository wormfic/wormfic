<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

require_once 'MaintenanceBootstrap.php';

try {
    $SesClient = new \Aws\SES\SESClient((new Config)->aws);

    $result = glob("../docs/email/*.json");

    var_dump($result);
} catch (\Throwable $exception) {
    file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
}
