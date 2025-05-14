<?php

use Adianti\Database\TRecord;

class StatusPagamento extends TRecord{
  const TABLENAME = 'statusPagamento';
  const PRIMARYKEY = 'id';

  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('status');
  }
}
?>