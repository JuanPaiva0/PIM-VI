<?php

use Adianti\Database\TRecord;

class Cargos extends TRecord{
  const TABLENAME = 'cargos';
  const PRIMARYKEY = 'id';

  public function __construct($id = null){
    parent::__construct($id);
    
    parent::addAttribute('cargo');
  }  
}
?>