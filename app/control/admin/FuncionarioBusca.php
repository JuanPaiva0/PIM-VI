<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class FuncionarioBusca extends TPage{
  private $datagrid;
  private $pageNavigation;
  private $loaded;
  private $form;
  
  public function __construct(){
    parent::__construct();
    if(!PermissionHelper::accessSupervisor()){
      new TMessage('error', 'Acesso restrito: apenas supervisores podem acessar essa tela',
      new TAction(['Home', 'onReload']));
      exit;
    }
    $this -> form = new BootstrapFormBuilder('form_busca_funcionario');
    $this -> form -> setFormTitle('Busca de Funcionario');

    $nome = new TEntry('nome');

    $this -> form -> addFields([new TLabel('Nome')], [$nome]);

    $this -> form -> addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    
    $this -> datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this -> datagrid -> wiidth = '100%';

    $col_id    = new TDataGridColumn('id', 'Id', 'left', '10%');
    $col_nome  = new TDataGridColumn('nome', 'Nome', 'left', '50%');
    $col_cargo = new TDataGridColumn('cargo->cargo', 'Cargo', 'center', '20%');
    $col_rg    = new TDataGridColumn('rg', 'RG', 'left', '20%');

    $this -> datagrid -> addColumn($col_id);
    $this -> datagrid -> addColumn($col_nome);
    $this -> datagrid -> addColumn($col_cargo);
    $this -> datagrid -> addColumn($col_rg);

    $action1 = new TDataGridAction(['FuncionarioForm', 'onEdit'], ['key' => '{id}']);
    $action2 = new TDataGridAction([$this, 'onDelete'], ['key' => '{id}']);

    $this -> datagrid -> addAction($action1, 'Editar', 'fa:edit blue');
    $this -> datagrid -> addAction($action2, 'Excluir', 'fa:trash red');

    $this -> datagrid -> createModel();

    $this -> pageNavigation = new TPageNavigation;
    $this -> pageNavigation -> setAction(new TAction([$this, 'onReload']));

    $panel = new TPanelGroup;
    $panel -> add($this -> form);
    $panel -> add($this -> datagrid);
    $panel -> addFooter($this -> pageNavigation);

    parent::add($panel);
  }

  public function onReload(){
    try {
      TTransaction::open('geek');

      $repository = new TRepository('Funcionarios');

      $criteria = new TCriteria;

      $funcionarios = $repository -> load($criteria);

      $this -> datagrid -> clear();
      if($funcionarios){
        foreach($funcionarios as $funcionario){
          $this -> datagrid -> addItem($funcionario);
        }
      }

      $this -> loaded = true;
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onDelete($param){ 
    $action = new TAction([__CLASS__, 'Delete']);
    $action -> setParameters($param);
    new TQuestion('Você realmente deseja excluir esse registro?', $action);
  }

  public function Delete($param){
    try {
      TTransaction::open('geek');
      
      $key = $param['key'];

      $funcionario = new Funcionarios;
      $funcionario -> delete($key);

      $pos_action = new TAction([__CLASS__, 'onReload']);
      new TMessage('info', 'Registro excluído com sucesso!', $pos_action);
      
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onSearch($param){
    try {
      TTransaction::open('geek');

      $data = $this -> form -> getData();
      $this -> form -> setData($data);
      $this -> datagrid -> clear();

      $criteria = new TCriteria;
      if($data -> nome){
        $criteria -> add(new TFilter('nome', 'like', "%{$data -> nome}%"));
      }

      $repository = new TRepository('Funcionarios');
      $funcionarios = $repository -> load($criteria);

      if($funcionarios){
        foreach($funcionarios as $funcionario){
          $this -> datagrid -> addItem($funcionario);
        }
      } else {
        new TMessage('info', 'Nenhum registro encontrado!');
      }

      $this -> loaded = true;
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onClear(){
    $this -> form -> clear();
    $this -> onReload();
  }

  public function show(){
    if(!$this -> loaded){
      $this -> onReload(func_get_arg(0));
    }
    parent::show();
  }
}
?>