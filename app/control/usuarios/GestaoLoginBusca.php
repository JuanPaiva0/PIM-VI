<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
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

    if(!PermissionHelper::accessSupervisor()){
      new TMessage('error', 'Acesso restrito: apenas supervisores podem acessar essa tela',
      new TAction(['Home', 'onReload']));
      exit;
    }

    $this -> form = new BootstrapFormBuilder('busca_login_form');
    $this -> form -> setFormTitle('Cadastro dos Usários');
    $this -> form -> setFieldSizes('100%');
    
    $nome = new TEntry('nome');

    $this -> form -> addFields([new TLabel('Nome'), $nome]);

    $this -> form -> addAction('Buscar', new TAction([$this, 'onBusca']), 'fa:search blue');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

    $this -> datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this -> datagrid -> width = '100%';

    $col_id = new TDataGridColumn('id', 'Id', 'left', '5%');
    $col_funcionario = new TDataGridColumn('nome','Funcionario', 'left', '30%');
    $col_login = new TDataGridColumn('login', 'Login', 'left', '30%');
    $col_cargo = new TDataGridColumn('cargo_obj->cargo', 'Cargo', 'center', '20%');
    $col_status = new TDataGridColumn('status', 'Status', 'center', '15%');

    $col_status -> setTransformer(function($value){
      $label = new TElement('span');
      $label -> style = 'text-shadow:none; font-size:12px; padding: 4px 8px; border-radius: 4px; color: white;';

      if($value){
        $label -> class = 'bg-success';
        $label -> add('Ativo');
      } else {
        $label -> class = 'bg-danger';
        $label -> add('Inativo');
      }

      return $label;
    });

    $this -> datagrid -> addColumn($col_id);
    $this -> datagrid -> addColumn($col_funcionario);
    $this -> datagrid -> addColumn($col_login);
    $this -> datagrid -> addColumn($col_cargo);
    $this -> datagrid -> addColumn($col_status);

    $action = new TDataGridAction(['GestaoLoginEdicao', 'onEdit'], ['key' => '{id}']);

    $this -> datagrid -> addAction($action, 'Editar', 'fa:edit blue');
    
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