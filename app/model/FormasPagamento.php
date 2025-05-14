<?php

use Adianti\Database\TRecord;

class FormasPagamento extends TRecord{
  const TABLENAME = 'formasPagamento';
  const PRIMARYKEY = 'id';

  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('forma');
  }

  
}
?>