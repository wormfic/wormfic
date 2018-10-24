<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

if (php_sapi_name() != "cli") {
    die("Maintenance can only be run from CLI. Goodbye!");
}

namespace Wormfic;

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../config.php";
