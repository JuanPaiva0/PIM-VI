<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFile;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;

class GestaoLoginForm extends TPage{
  private $form;
  private $funcionario_list;
  public function __construct(){
    parent::__construct();

    $this -> form = new BootstrapFormBuilder('gestao_login_form');
    $this -> form -> setFormTitle('Gestão de Login');

    $nome_search  = new TEntry('nome_search');
    $cargo_search = new TEntry('cargo_search');
    $nome_search  -> placeholder = 'Nome';
    $cargo_search -> placeholder = 'Cargo';

    $this -> funcionario_list = new TCheckList('funcionario_list');
    $this -> funcionario_list -> setHeight(200);
    //$this -> funcionario_list -> setDirection('vertical');
    $this -> funcionario_list -> setSelectAction(new TAction([$this, 'onSelect']));

    $hbox = new THBox;
    $hbox->style = 'border-bottom: 1px solid gray;padding-bottom:10px';
    $hbox -> add(new TLabel('Usuário sem login'));
    $hbox->add($nome_search)->style = 'float:right;width:30%;margin-right:20px;';
    $hbox->add($cargo_search)->style = 'float:right;width:30%;';

    $this -> carregaFuncionarioSemLogin();

    $this -> form -> addContent([$hbox]);
    $this -> form -> addFields([$this -> funcionario_list]); 
    
    parent::add($this -> form);
  }

  public function carregaFuncionarioSemLogin(){
    try {
      TTransaction::open('geek');
     
      $logins = SystemUsers::all();
      $ids_login = array_map(fn($user) => $user -> funcionario_id, $logins);

      $criteria = new TCriteria;
      if($ids_login){
        $criteria -> add(new TFilter('id', 'NOT IN', $ids_login));
      }

      $repository = new TRepository('Funcionarios');
      $funcionarios = $repository -> load($criteria);

      $lista = [];
      foreach($funcionarios as $f){
        $lista[$f->id] = ['name' => "{$f->nome} ({$f->cpf})"];
      }

      $this -> funcionario_list ->addItems($lista);
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public static function onSelect(){
     
  }
}
?>