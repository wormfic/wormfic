<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

/**
 * send php session nonsense to the database in case we get big enough to need A FUCKING SERVER FARM
 *
 * @todo store addr/user-agent info for purposes of users identifying which sess is which.
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class SessionHandler
{

    public static function destroy(string $sessionId): bool
    {

        $query = Database::get()->prepare("delete from `data` "
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "string");
        $query->execute();
        return (bool) $query->rowCount();
    }

    public static function read(string $sessionId): string
    {
        $query = Database::get()->prepare("select `data` from users__sessions"
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "string");
        $query->execute();
        $data  = $query->fetchColumn();

        return is_string($data) ? $data : "";
    }

    public static function write(string $sessionId, string $sessionData = "{}", ?\Psr\Http\Message\RequestInterface $req = null): bool
    {
        if ($req instanceof \Psr\Http\Message\RequestInterface) {
            $query = Database::get()->prepare('INSERT INTO users__sessions '
            . '(`idSession`, `data`, `changed`, `userAgent`) VALUES(?, ?, NOW(), ?) '
            . 'ON DUPLICATE KEY UPDATE `data`=VALUES(`data`), '
            . '`changed`=VALUES(`changed`), `userAgent`=VALUES(`userAgent`);', ['string', 'string', 'string']);
            $query->bindValue(3, $req->getHeader("User-Agent")[0] ?? null);
        } else {
            $query = Database::get()->prepare('INSERT INTO users__sessions '
            . '(`idSession`, `data`, `changed`) VALUES(?, ?, NOW()) '
            . 'ON DUPLICATE KEY UPDATE `data`=VALUES(`data`), '
            . '`changed`=VALUES(`changed`);', ['string', 'string']);
        }
        $query->bindValue(1, $sessionId);
        $query->bindValue(2, $sessionData);
        $query->execute();
        return (bool) $query->rowCount();
    }

    public static function validateId(string $sessionId): bool
    {

        $query = Database::get()->prepare("select count(*) from users__sessions "
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "string");
        $query->execute();
        return (bool) $query->fetchColumn();
    }

    public static function updateTimestamp(string $sessionId, ?\Psr\Http\Message\RequestInterface $req = null): bool
    {

        if ($req instanceof \Psr\Http\Message\RequestInterface) {
            $query = Database::get()->prepare('UPDATE users__sessions '
            . 'SET changed = NOW(), userAgent = ? '
            . 'where idSession = ?;', ['string', 'string']);
            $query->bindValue(2, $req->getHeader("User-Agent")[0] ?? null);
        } else {
            $query = Database::get()->prepare('UPDATE users__sessions '
            . 'SET changed = NOW() where idSession = ?;', ['string']);
        }
        $query->bindValue(1, $sessionId);
        $query->execute();
        return (bool) $query->rowCount();
    }
}
