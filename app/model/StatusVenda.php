<?php

use Adianti\Database\TRecord;

class StatusVenda extends TRecord{
  const TABLENAME = 'statusVenda';
  const PRIMARYKEY = 'id';

  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('status');
  }
}
?>