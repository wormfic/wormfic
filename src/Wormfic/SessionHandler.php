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
class SessionHandler implements \SessionHandlerInterface, \SessionIdInterface, \SessionUpdateTimestampHandlerInterface
{

    public function close(): bool
    {
        return true;
    }

    public function open($save_path, $session_name): bool
    {
        return true;
    }

    public function create_sid(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function destroy($sessionId): bool
    {

        $query = Database::get()->prepare("delete from `data` "
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "string");
        $query->execute();
        return (bool) $query->rowCount();
    }

    public function gc($maximumLifetime): int
    {
        try {
            $query = Database::get()->prepare("delete from `data` "
            . "where changed <= ?");
            $query->bindValue(1, $maximumLifetime, "integer");
            $query->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function read($sessionId): string
    {
        $query = Database::get()->prepare("select `data` from users__sessions "
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "string");
        $query->execute();
        $data  = $query->fetchColumn();

        return is_string($data) ? $data : "";
    }

    public function write($sessionId, $sessionData = "{}"): bool
    {
        $query = Database::get()->prepare('INSERT INTO users__sessions '
        . '(`idSession`, `data`, `changed`, `userAgent`, `addr`) VALUES(?, ?, NOW(), ?, INET6_ATON(?)) '
        . 'ON DUPLICATE KEY UPDATE `data`=VALUES(`data`), '
        . '`changed`=VALUES(`changed`), `userAgent`=VALUES(`userAgent`), '
        . '`addr`=VALUES(`addr`);', ['string', 'string', 'string', 'string']);

        $query->bindValue(1, $sessionId);
        $query->bindValue(2, $sessionData);
        $query->bindValue(3, $_SERVER['HTTP_USER_AGENT'] ?? null);
        $query->bindValue(4, str_replace(['[', ']'], '', $_SERVER['REMOTE_ADDR']) ?? null);
        $query->execute();
        return (bool) $query->rowCount();
    }

    public function validateId($sessionId): bool
    {

        $query = Database::get()->prepare("select count(*) from users__sessions "
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "string");
        $query->execute();
        return (bool) $query->fetchColumn();
    }

    public function updateTimestamp($sessionId, $val): bool
    {
        $query = Database::get()->prepare('INSERT INTO users__sessions '
        . '(`idSession`, `changed`, `userAgent`, `addr`) VALUES(?, NOW(), ?, INET6_ATON(?)) '
        . 'ON DUPLICATE KEY UPDATE `changed`=VALUES(`changed`), `userAgent`=VALUES(`userAgent`), '
        . '`addr`=VALUES(`addr`);', ['string', 'string', 'string']);

        $query->bindValue(1, $sessionId);
        $query->bindValue(2, $_SERVER['HTTP_USER_AGENT'] ?? null);
        $query->bindValue(3, str_replace(['[', ']'], '', $_SERVER['REMOTE_ADDR']) ?? null);
        $query->execute();
        return (bool) $query->rowCount();
    }
}
