<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class GestaoLoginBusca extends TPage{
  private $datagrid;
  private $form;
  private $loaded;
  public function __construct(){
    parent::__construct();

    $this -> form = new BootstrapFormBuilder('busca_login_form');
    $this -> form -> setFormTitle('Cadastro dos Usários');
    $this -> form -> setFieldSizes('100%');
    
    $nome = new TEntry('nome');

    $this -> form -> addFields([new TLabel('Nome'), $nome]);

    $this -> form -> addAction('Buscar', new TAction([$this, 'onBusca']), 'fa:search blue');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

    $this -> datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this -> datagrid -> width = '100%';

    $col_id = new TDataGridColumn('id', 'Id', 'left', '10%');
    $col_funcionario = new TDataGridColumn('nome','Funcionario', 'center', '40%');
    $col_login = new TDataGridColumn('login', 'Login', 'left', '30%');
    $col_cargo = new TDataGridColumn('cargo->cargo', 'Cargo', 'center', '20%');
    $col_status = new TDataGridColumn('status', 'Status', 'center', '10%');

    $this -> datagrid -> addColumn($col_id);
    $this -> datagrid -> addColumn($col_funcionario);
    $this -> datagrid -> addColumn($col_login);
    $this -> datagrid -> addColumn($col_cargo);
    $this -> datagrid -> addColumn($col_status);

    $this -> datagrid -> createModel();

    $panel = new TPanelGroup;
    $panel -> add($this -> form);
    $panel -> add($this -> datagrid);
    
    
    parent::add( $panel );
  }

  public function onReload(){
    try {
      TTransaction::open('geek');

      $repository = new TRepository('SystemUsers');
      $criteria = new TCriteria;

      $cadastros = $repository -> load($criteria);

      $this -> datagrid -> clear();

      print '<pre>';
      print_r($cadastros);
      print '</pre>';
      
      if($cadastros){
        foreach($cadastros as $cadastro){
        $this -> datagrid -> addItem($cadastro);    
        }
      }

      $this -> loaded = true;
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onBusca($param){
    
  }

  public function onClear(){
    $this -> form -> clear();
  }

  public function show(){
    if(!$this -> loaded){
      $this -> onReload(func_get_arg(0));
    }
    parent::show();
  }
}
?>