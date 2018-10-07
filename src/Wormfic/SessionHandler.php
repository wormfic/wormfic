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

    public function destroy(string $sessionId): bool
    {
        try {
            $query = Database::get()->prepare("delete from `data` "
            . "where idSession = ?");
            $query->bindValue(1, $sessionId, "integer");
            $query->execute();
            return (bool) $query->rowCount();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function gc(int $maximumLifetime): int
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

    public function open(string $sessionSavePath, string $sessionName): bool
    {
        try {
            $db = Database::get();
            if (!$db->isConnected()) {
                $db->connect();
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function read(string $sessionId): string
    {
        $query = Database::get()->prepare("select `data` from users__sessions"
        . "where idSession = ?");
        $query->bindValue(1, $sessionId, "integer");
        $query->execute();
        $data  = $query->fetchColumn();

        return is_string($data) ? $data : "";
    }

    public function write(string $sessionId, string $sessionData): bool
    {
        try {
            $query = Database::get()->prepare('INSERT INTO users__sessions '
            . '(`idSession`, `data`, `changed`) VALUES(?, ?, NOW()) '
            . 'ON DUPLICATE KEY UPDATE `data`=VALUES(`data`), '
            . '`changed`=VALUES(`changed`);', ['integer', 'string']);
            $query->bindValue(1, $sessionId);
            $query->bindValue(2, $sessionData);
            $query->execute();
            return (bool) $query->rowCount();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function create_sid(): string
    {
        return Snowflake::generate();
    }

    public function validateId(string $sessionId): bool
    {
        try {
            $query = Database::get()->prepare("select count(*) from users__sessions"
            . "where idSession = ?");
            $query->bindValue(1, $sessionId, "integer");
            $query->execute();
            return (bool) $query->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function updateTimestamp(string $sessionId, string $sessionData): bool
    {
        try {
            $query = Database::get()->prepare('UPDATE users__sessions '
            . 'SET changed = NOW() where idSession = ?;', ['integer']);
            $query->bindValue(1, $sessionId);
            $query->execute();
            return (bool) $query->rowCount();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
