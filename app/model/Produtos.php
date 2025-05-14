<?php

use Adianti\Database\TRecord;

class Produtos extends TRecord{
  const TABLENAME = 'produtos';
  const PRIMARYKEY = 'id';
  const IDPOLICY = 'max'; 

  private $categoria;
  private $fabricante;

  public function __construct($id = null){
    parent::__construct($id);

    parent::addAttribute('nome');
    parent::addAttribute('barcode');
    parent::addAttribute('categoria_id');
    parent::addAttribute('fabricante_id');    
    parent::addAttribute('plataforma');    
    parent::addAttribute('prazoGarantia');    
    parent::addAttribute('estoque');    
    parent::addAttribute('preco');    
  }

  public function get_categoria(){
    if(empty($this -> categoria)){
      $this -> categoria = new Categorias($this -> categoria_id);
    }
    return $this -> categoria;
  }

  public function get_fabricante(){
    if(empty($this -> fabricante)){
      $this -> fabricante = new Fabricantes($this -> fabricante_id);
    }
    return $this -> fabricante;
  }
}
?>