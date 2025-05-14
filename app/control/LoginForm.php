<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TModalForm;
use Adianti\Widget\Form\TPassword;

class LoginForm extends TPage{
  private $form;
  public function __construct(){
   parent::__construct();

   $this -> form = new TModalForm('form_login');
   $this -> form -> setFormTitle('LOGIN');
   
   $login    = new TEntry('login');
   $password = new TPassword('password');

   $this -> form -> addRowField('Login', $login);
   $this -> form -> addRowField('Password', $password);

   $this -> form -> addAction('Log In', new TAction([$this, 'onLogin']), '');
   
   parent::add($this -> form); 
  }

  public function onLogin($param){
    try {
      $data = $this -> form -> getData();

      print_r($data);

      AuthServices::authenticate($data -> login, $data -> password);
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }
}
?>