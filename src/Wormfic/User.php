<?php

/*
 * Copyright (c) 2018 WormFic.net
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Wormfic;

use Carbon\Carbon;

/**
 * This handles all of our user magic.
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class User
{
    const PASS_PARAMS = ['time_cost' => 200];
    const PASS_ALGO   = \PASSWORD_ARGON2ID;

    /**
     * Usernames that can't be taken. We do basic spoof checking with intl.
     */
    const PROTECTED_USERNAMES = ['root', 'webmaster', 'admin', 'administrator', 'mod', 'moderator'];

    public $idUser;
    public $username;
    private $password;
    public $email;

    /**
     *
     * @var Carbon
     */
    public $created;

    /**
     *
     * @var Carbon
     */
    public $updated;
    public $status;

    /**
     *
     * @var Carbon
     */
    public $birthday;
    public $gender;

    /**
     *
     * @var Blob
     */
    public $profile;
    public $language = "en-US";
    public $timezone = "UTC";

    public function __construct(int $idUser, string $username, string $password, string $email, Carbon $created, Carbon $updated, string $status)
    {
        $this->idUser   = $idUser;
        $this->username = $username;
        $this->password = $password;
        $this->email    = $email;
        $this->created  = $created;
        $this->updated  = $updated;
        $this->status   = $status;
    }

    public static function fromSession(): ?User
    {
        if (array_key_exists("idUser", $_SESSION)) {
            return self::fromID($_SESSION['idUser']);
        }
        return null;
    }

    public static function fromID(int $id): ?User
    {
        $query  = Database::get()->prepare("select * from users "
        . "where idUser = ?");
        $query->bindValue(1, $id, "integer");
        $query->execute();
        $dbData = $query->fetch();
        if ($dbData) {
            return self::dbArrayToObject($dbData);
        }
        return null;
    }

    public static function fromName(string $username): ?User
    {
        $query  = Database::get()->prepare("select * from users "
        . "where username = ?");
        $query->bindValue(1, $username, "string");
        $query->execute();
        $dbData = $query->fetch();
        if ($dbData) {
            return self::dbArrayToObject($dbData);
        }
        return null;
    }

    public static function login(string $username, string $givenPassword): User
    {
        $user = self::fromName($username);

        if ($user->verifyPassword($givenPassword)) {
            return $user;
        } else {
            throw new Exception\LoginException("incorrectPassword");
        }
    }

    public function verifyPassword(string $givenPassword): bool
    {
        if (password_verify($givenPassword, self::PASS_ALGO, self::PASS_PARAMS)) {
            if (password_needs_rehash($this->password, self::PASS_ALGO, self::PASS_PARAMS)) {
                $this->setPassword($givenPassword, true);
                $this->auditLog("pass_HashUpdateAuto");
            }
            $this->auditLog("pass_verifySuccess");
            return true;
        }
        $this->auditLog("pass_verifyFail");
        return false;
    }

    public function setPassword(string $newPassword, bool $ignoreValidation = false): bool
    {
        if (!$ignoreValidation && !self::validatePasswordSecurity($newPassword)) {
            return false;
        }
        $query = Database::get()->prepare('UPDATE users '
        . 'set `password` = ? '
        . 'where `idUser` = ?;', ['string', 'integer']);

        $query->bindValue(1, password_hash($newPassword, self::PASS_ALGO, self::PASS_PARAMS));
        $query->bindValue(2, $this->idUser);
        $query->execute();
        return (bool) $query->rowCount();
    }

    public static function registerAccount(string $username, string $pass, string $email): User
    {
        // name validation
        $nameTaken = Database::get()->prepare("select * from users "
        . "where username = ?");
        $nameTaken->bindValue(1, $username, "string");
        $nameTaken->execute();
        if ($nameTaken->fetch()) {
            throw new Exception\RegistrationException("usernameTaken");
        }
        $checker = new \Spoofchecker();
        foreach (self::PROTECTED_USERNAMES as $name) {
            if ($checker->areConfusable($name, $username) || $checker->areConfusable($name, mb_strtolower($username))) {
                throw new Exception\RegistrationException("protectedUsername");
            }
        }

        // email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception\RegistrationException("invalidEmail");
        }

        // password security validation
        if (!self::validatePasswordSecurity($pass)) {
            throw new Exception\RegistrationException("invalidPassword");
        }

        // make it!
        $userID = Snowflake::generate();
        $query  = Database::get()->prepare('INSERT INTO users '
        . '(`username`, `password`, `email`, `idUser`, `status`) '
        . 'VALUES(?, ?, ?, ?, "active");', ['string', 'string', 'string', 'integer']);

        $query->bindValue(1, $username);
        $query->bindValue(2, password_hash($pass, self::PASS_ALGO, self::PASS_PARAMS));
        $query->bindValue(3, $email);
        $query->bindValue(4, $userID);
        $query->execute();

        $user = self::fromID($userID);
        $user->auditLog("user_register");
        return $user;
    }

    public function auditLog(string $action, int $item = null, array $data = null): void
    {
        $logID = Snowflake::generate();
        $query = Database::get()->prepare('INSERT INTO users__audit '
        . '(`idUser`, `logTime`, `logAction`, `logAddr`, `logItem`, `logData`, `idAudit`) '
        . 'VALUES(?, NOW(), ?, INET6_ATON(?), ?, ?, ?);', ['integer', 'string', 'string', 'string', 'string', 'integer']);

        $query->bindValue(1, $this->idUser);
        $query->bindValue(2, $action);
        $query->bindValue(3, str_replace(['[', ']'], '', $_SERVER['REMOTE_ADDR']) ?? null);
        $query->bindValue(4, $item);
        $query->bindValue(5, $data);
        $query->bindValue(6, $logID);
        $query->execute();
    }

    private static function dbArrayToObject(array $dbData): User
    {
        // todo verify account hasn't been delet or something

        $x = new User($dbData['idUser'], $dbData['username'], $dbData['password'], $dbData['email'], (new Carbon($dbData['created'])), (new Carbon($dbData['updated'])), $dbData['status']);

        if (!is_null($dbData['profile_renderEngine']) && !is_null($dbData['profile_content'])) {
            $x->profile = new Blob($dbData['profile_renderEngine'], $dbData['profile_content']);
        }
        if (!is_null($dbData['birthday'])) {
            $x->birthday = new Carbon($dbData['birthday']);
        }
        if (!is_null($dbData['gender'])) {
            $x->gender = $dbData['gender'];
        }

        return $x;
    }

    public static function validatePasswordSecurity(string $pass)
    {
        return !(stripos($pass, "\0") !== false || strlen(trim($pass)) == 0);
    }
}
