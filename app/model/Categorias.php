<?php

use Adianti\Database\TRecord;

class Categorias extends TRecord{
  const TABLENAME = 'categorias';
  const PRIMARYKEY = 'id';
  
  public function __construct($id = null){
    parent::__construct($id);
    
    parent::addAttribute('nome');
  }
}
?>