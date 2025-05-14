<?php

use Adianti\Database\TRecord;

class Fabricantes extends TRecord{
  const TABLENAME = 'fabricantes';
  const PRIMARYKEY = 'id';

  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('nome');
  }
}
?>