<?php

use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRecord;
use Adianti\Database\TRepository;

class Funcionarios extends TRecord{
  const TABLENAME = 'funcionarios';
  const PRIMARYKEY = 'id';
  const IDPOLICY = 'max'; 

  private $cargos;
  
  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('rg');
    parent::addAttribute('cpf');
    parent::addAttribute('nome');
    parent::addAttribute('endereco');
    parent::addAttribute('telefone');
    parent::addAttribute('email');
    parent::addAttribute('cargo_id');
  }

  public function get_cargo(){
    if(empty($this -> cargos)){
      $this -> cargos = new Cargos($this -> cargo_id);
    }
    return $this -> cargos;
  }


  public function existeRG($rg, $id = null){
    $criteria = new TCriteria;
    $criteria -> add((new TFilter('rg', '=', $rg)));

    if($id){
      $criteria -> add(new TFilter('id', '<>', $id));
    }

    $repository = new TRepository(__CLASS__);
    $count = $repository -> count($criteria);
    
    return $count > 0;
  }
}
?>