<?php

use Adianti\Registry\TSession;

class PermissionHelper{
  public static function accessSupervisor(){
    $user = TSession::getValue('user');
    return $user -> cargo == 1; 
  }

  public static function accessAtendente(){
     $user = TSession::getValue('user');
    return in_array($user -> cargo, [1, 2]);
  }

  public static function accessEstoquista(){
     $user = TSession::getValue('user');
    return in_array($user -> cargo, [1, 3]);
  }
}
?>