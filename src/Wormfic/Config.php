<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

/**
 * Hold all of our config stuff, letting us access it wherever.
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class Config
{
    /**
     * Our config storage
     * @var array
     */
    private static $config = [];

    public function __get(string $name)
    {
        return self::$config[$name];
    }

    public function __set(string $name, $value)
    {
        self::$config[$name] = $value;
    }
}
