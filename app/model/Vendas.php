<?php

use Adianti\Database\TRecord;

class Vendas extends TRecord{
  const TABLENAME = 'vendas';
  const PRIMARYKEY = 'id';
  const IDPOLICY = 'max';

  public function __construct($id = null){
   parent::__construct($id);

   parent::addAttribute('funcionario_id');
   parent::addAttribute('cliente_id');
   parent::addAttribute('dataVenda');
   parent::addAttribute('valorTotal');
   parent::addAttribute('formaPagamento');
   parent::addAttribute('statusPagamento');
   parent::addAttribute('statusVenda');
  }
}
?>