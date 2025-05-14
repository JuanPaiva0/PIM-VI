<?php

use Adianti\Database\TRecord;

class ItensVenda extends TRecord{
  const TABLENAME = 'itensVenda';
  const PRIMARYKEY = 'id';
  const IDPOLICY = 'serial'; 

  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('venda_id');
    parent::addAttribute('produto_id');
    parent::addAttribute('quantidade');
    parent::addAttribute('subtotal');
  }
}
?>