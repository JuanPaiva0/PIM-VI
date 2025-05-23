<?php
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;

class AuthServices {
  public static function authenticate($login, $password){
    TTransaction::open('geek');

    $criteria = new TCriteria();
    $criteria->add(new TFilter('login', '=', $login));
    $criteria->add(new TFilter('status', '=', true));

    $repository = new TRepository('SystemUsers');
    $users = $repository->load($criteria);

    if ($users) {
      $user = $users[0];      
      if (password_verify($password, $user->password)) {
        TSession::setValue('user', $user);
        TSession::setValue('logged', true);
        
        TTransaction::close();

        echo "<script>window.location = 'index.php?class=Home';</script>";
        exit;
      } else {
        throw new Exception('Senha inválida!');
      }
    } else {
      throw new Exception('Usuário não encontrado ou inativo!'); 
    }

    TTransaction::close();
    throw new Exception('Credenciais inválidas ou usuário inativo!');
  }

  public static function logout(){
    TSession::clear();
    echo "<script>window.location = 'index.php?class=LoginForm';</script>";
    exit;
  }
}