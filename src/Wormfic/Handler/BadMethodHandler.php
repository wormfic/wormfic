<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic\Handler;

/**
 * Description of BadMethodHandler
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class BadMethodHandler
{

    public function respond(array $vars): string
    {
        http_response_code(405);
        $loader = new \Twig\Loader\FilesystemLoader('tpl');
        $twig   = new \Twig\Environment($loader, [
            'cache' => false,
        ]);

        return $twig->render("405.twig", $vars);
    }
}
