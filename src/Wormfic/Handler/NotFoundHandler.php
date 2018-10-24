<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic\Handler;

/**
 * Description of NotFoundHandler
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class NotFoundHandler
{

    public function respond(array $vars): string
    {
        http_response_code(404);
        $loader = new \Twig\Loader\FilesystemLoader('tpl');
        $twig   = new \Twig\Environment($loader, [
            'cache' => false,
        ]);

        return $twig->render("404.twig", $vars);
    }
}
