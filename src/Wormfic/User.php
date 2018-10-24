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
class User {

  public $idUser;
  public $username;
  private $password;
  public $created;
  public $updated;
  public $status;
  public $birthday;
  public $gender;
  public $profile;

  public function __construct(int $idUser, string $username, string $password, Carbon $created, Carbon $updated, string $status) {
    $this->idUser = $idUser;
    $this->username = $username;
    $this->password = $password;
    $this->created = $created;
    $this->updated = $updated;
    $this->status = $status;
  }

  public static function fromSession(string $sessionData) {
    $data = json_decode($sessionData);
    if (property_exists($data, "idUser")) {
      $query = Database::get()->prepare("select * from users"
        . "where idUser = ?");
      $query->bindValue(1, $data->idUser, "integer");
      $query->execute();
      $dbData = $query->fetch();
      // birthday, gender, profile_renderEngine, profile_content
      // construct the initial object
      $x = new User($dbData['idUser'], $dbData['sessionData'], $dbData['password'], (new Carbon($dbData['created'])), (new Carbon($dbData['updated'])), $dbData['status']);

      if (array_key_exists('profile_renderEngine', $dbData) && array_key_exists('profile_content', $dbData)) {

      }
    }
  }

}
