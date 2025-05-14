<?php
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;

class AuthServices {
  public static function authenticate($login, $password){
    TTransaction::open('geek');
    
    $criteria = new TCriteria();
    $criteria -> add(new TFilter('login', '=', $login));
    $criteria -> add(new TFilter('status', '=', true));

    $repository = new TRepository('SystemUsers');
    $user = $repository -> load($criteria);

    if($user){
      $user = $user[0];

      if($user -> password === $password){
        TSession::setValue('user', $user);
         TTransaction::close();
         return true;
      }
    }
   TTransaction::close();
    throw new Exception('Credenciais inválidas ou usuário inativo!');
  }

  public static function logout(){
    TSession::clear();
    AdiantiCoreApplication::loadPage('LoginForm');
  }
}
?>