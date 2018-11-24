<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic\Handler;

use Wormfic\User;

/**
 * Description of NotFoundHandler
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class AccountHandler
{

    public function respond(array $vars): string
    {
        http_response_code(404);
        $loader = new \Twig\Loader\FilesystemLoader('tpl');
        $twig   = new \Twig\Environment($loader, [
            'cache' => false,
        ]);

        $user = User::fromSession();

        if (is_null($user) && array_key_exists('login', $_REQUEST)) {
            // login nonsense
        } elseif (is_null($user) && array_key_exists('register', $_REQUEST)) {
            // register nonsense
        } elseif ($user instanceof User) {
            // user account page
        } else {
            // form
            return $twig->render("AccountHandlerLoginForm.twig", $vars);
        }
        var_dump($_REQUEST);
    }
}
