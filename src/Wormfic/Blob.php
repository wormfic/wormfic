<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

/**
 * Description of Blob
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class Blob
{
    public $renderEngine;
    public $body;
    public $id;

    public function __construct(string $renderEngine, string $body)
    {

    }

    public static function createCommonBlob(string $renderEngine, string $body): Blob
    {

    }

    public static function createFromID(int $id): Blob
    {

    }
}
