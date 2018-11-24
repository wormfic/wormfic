<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

use FastRoute;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";
Database::make();
session_set_save_handler((new SessionHandler()));
session_start();

$dispatcher = FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', 'HomepageHandler');

    // user stuff
    $r->addRoute(['GET', 'POST'], '/user[/{action}]', 'AccountHandler');
    $r->addRoute('GET', '/users/{uid}', 'UserHandler');

    // works stuff
    $r->addRoute('GET', '/works/{work}', 'WorkHandler');
    $r->addRoute('GET', '/chapters/{chapter}', 'ChapterHandler');
});

$uri = $_SERVER['REQUEST_URI'];
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $vars    = ['uri' => $uri];
        $handler = new Handler\NotFoundHandler();
        echo $handler->respond($vars);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // TODO: return json as needed?
        $vars    = ['allowedMethods' => $routeInfo[1]];
        $handler = new Handler\BadMethodHandler();
        echo $handler->respond($vars);
        break;
    case FastRoute\Dispatcher::FOUND:
        $hname   = "Wormfic\\Handler\\{$routeInfo[1]}";
        $handler = new $hname();
        echo $handler->respond($routeInfo[2]);
        break;
}
