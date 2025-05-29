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

class ClientesBusca extends TPage{
  private $form;
  private $datagrid; 
  private $pageNavigation;
  private $loaded;
  
  public function __construct(){
    parent::__construct();
    if(!PermissionHelper::accessAtendente()){
        new TMessage('error', 'Acesso restrito: apenas atendentes e supervisores podem acessar essa tela',
        new TAction(['Home', 'onReload']));
        exit;
      }
    
    $this -> form = new BootstrapFormBuilder('form_clientes_busca');
    $this -> form -> setFormTitle('Busca de Clientes');
    $this -> form -> setFieldSizes('80%');

    $nome = new TEntry('nome');
    $cpf = new TEntry('cpf');

    $cpf -> setMask('999.999.999-99');

    $row = $this -> form -> addFields( [new TLabel('Nome'), $nome],
                                       [new TLabel('CPF'), $cpf]);
    $row -> layout = ['col-sm-6', 'col-sm-6'];

    $this -> form -> addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    
    $this -> datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this -> datagrid -> width = '100%';
    
    $col_id = new TDataGridColumn('id', 'Id', 'left', '5%');
    $col_nome = new TDataGridColumn('nome', 'Nome', 'center', '19%');
    $col_rg = new TDataGridColumn('rg', 'RG', 'center', '19%');
    $col_cpf = new TDataGridColumn('cpf', 'CPF', 'center', '19%');
    $col_data_registro = new TDataGridColumn('dataRegistro', 'Data de Registro', 'center', '19%');
    $col_telefone = new TDataGridColumn('telefone', 'Telefone', 'center', '19%');

    $this -> datagrid -> addColumn($col_id);
    $this -> datagrid -> addColumn($col_nome);
    $this -> datagrid -> addColumn($col_rg);
    $this -> datagrid -> addColumn($col_cpf);
    $this -> datagrid -> addColumn($col_data_registro);
    $this -> datagrid -> addColumn($col_telefone);

    $action1 = new TDataGridAction(['ClientesForm', 'onEdit'], ['key' => '{id}']);
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

  public function onReload($param){
    try {
      TTransaction::open('geek');
      $repository = new TRepository('Clientes');
      $limit = 10;
      
      if(isset($param['order'])){
        $param['order'] = 'id';
        $param['direction'] = 'asc';
      }
      
      $criteria = new TCriteria;
      $criteria -> setProperties($param);
      $criteria -> setProperty('limit', $limit);

      $clientes = $repository -> load($criteria);
      $this -> datagrid -> clear();
      if($clientes){
        foreach($clientes as $cliente){
          $this -> datagrid -> addItem($cliente);
        }
      }
      $criteria -> resetProperties();
      $count = $repository -> count($criteria);

      $this -> pageNavigation -> setCount($count);
      $this -> pageNavigation -> setProperties($param);
      $this -> pageNavigation -> setLimit($limit);
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onSearch($param){
    try {
      TTransaction::open('geek');
      $dados = $this -> form -> getData();
      $this -> form -> setData($dados);
      $this -> datagrid -> clear();
      
      $criteria = new TCriteria;

      if($dados -> nome and $dados -> cpf){
        new TMessage('error', 'Informar apenas uma informação de busca!');
      } else {
        if($dados -> nome){
          $criteria -> add(new TFilter('nome', 'like', "%{$dados -> nome}%"));
        } else if($dados -> cpf){
          $criteria -> add(new TFilter('cpf', 'like', "%{$dados -> cpf}%"));
        }
      }

      $repository = new TRepository('Clientes');
      $clientes = $repository -> load($criteria);

      if($clientes){
        foreach($clientes as $cliente){
          $this -> datagrid -> addItem($cliente);
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
      $cliente = new Clientes;
      $cliente -> delete($key);

      $pos_action = new TAction([__CLASS__, 'onReload']);
      new TMessage('info', 'Registro excluído com sucesso!', $pos_action);
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
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