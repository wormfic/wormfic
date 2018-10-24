<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

use Spiral\Goridge;
use Spiral\RoadRunner;
use FastRoute;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;

ini_set('display_errors', 'stderr');

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";

$dispatcher = FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/works/{work}', 'WorkHandler');
    $r->addRoute('GET', '/chapters/{chapter}', 'ChapterHandler');
    $r->addRoute('GET', '/users/{uid}', 'UserHandler');
    $r->addRoute('GET', '/user[/{action}]', 'AccountHandler');
});

Database::make();

$worker = new RoadRunner\Worker(new Goridge\StreamRelay(STDIN, STDOUT));
$psr7   = new RoadRunner\PSR7Client($worker);

while ($req = $psr7->acceptRequest()) {
    try {

        $resp   = new \Zend\Diactoros\Response();
        $cookie = FigRequestCookies::get($req, 'wormficSession');
        if (is_null($cookie->getValue()) || !SessionHandler::validateId($cookie->getValue())) {
            $token = bin2hex(random_bytes(32));
            $resp  = FigResponseCookies::set($resp, \Dflydev\FigCookies\SetCookie::create('wormficSession')
            ->withValue($token)
            ->withPath("/")
            ->withSameSite(\Dflydev\FigCookies\Modifier\SameSite::strict())
            ->rememberForever()
            ->withHttpOnly()
            );
            SessionHandler::write($token, "{}", $req);
        } else {
            SessionHandler::updateTimestamp($cookie);
            $worker->error("COOKIE VALID");
        }



        // add these headers to EVERYTHING...
        $resp = $resp->withHeader('X-Frame-Options', 'DENY')
        ->withHeader('X-XSS-Protection', '1; mode=block')
        ->withHeader('Strict-Transport-Security', 'max-age=8640000; includeSubDomains')
        ->withHeader("Content-Security-Policy", "default-src 'self' *.wormfic.net")
        ;

        $routeInfo = $dispatcher->dispatch($req->getMethod(), $req->getUri()->getPath());
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                break;
            case FastRoute\Dispatcher::FOUND:
                $handler        = $routeInfo[1];
                $vars           = $routeInfo[2];
                // ... call $handler with $vars
                break;
        }


        $resp = $resp->withHeader("Content-Type", "application/json");
        $resp->getBody()->write(json_encode($req->getHeaders()));

        $psr7->respond($resp);
    } catch (\Throwable $e) {
        $psr7->getWorker()->error((string) $e);
    }
}
