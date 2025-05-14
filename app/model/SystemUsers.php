<?php

use Adianti\Database\TRecord;

class SystemUsers extends TRecord{
  const TABLENAME = 'system_users';
  const PRIMARYKEY = 'id';
  const IDPOLICY = 'max'; 

  private $cargo;
  
  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('nome');
    parent::addAttribute('login');
    parent::addAttribute('password');
    parent::addAttribute('email');
    parent::addAttribute('cargo');
    parent::addAttribute('funcionario_id');
    parent::addAttribute('status');
  }
  public function get_cargo(){
    if(empty($this -> cargo)){
      $this -> cargo = new Cargos($this -> cargo_id);
    }
    return $this -> cargo;
  }
}
?>