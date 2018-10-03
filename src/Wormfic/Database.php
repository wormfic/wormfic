<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

/**
 * This is just a little friend to hold our static db object, so we can
 * summon it anywhere.
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class Database
{
    /**
     * Our DB object. Sacred is thy name.
     * @var \Doctrine\DBAL\Connection
     */
    private static $db = null;

    /**
     * Get a reference to the db object. :snug:
     * @return \Doctrine\DBAL\Connection
     * @throws \Exception
     */
    public static function get(): \Doctrine\DBAL\Connection
    {
        if (is_null(self::$db)) {
            throw new \Exception("Database not set up! Have you run Database::make() yet?");
        }
        return self::$db;
    }

    /**
     * Initialize the database.
     * @return void
     */
    public static function make(): void
    {
        self::$db = \Doctrine\DBAL\DriverManager::getConnection(['url' => (new Config)->database], new \Doctrine\DBAL\Configuration());
    }
}
