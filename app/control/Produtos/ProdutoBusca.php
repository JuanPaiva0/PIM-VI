<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TBarCodeInputReader;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ProdutoBusca extends TPage{
  private $form;
  private $datagrid;
  private $pageNavigation;
  private $loaded;

  public function __construct(){
    parent::__construct();

    $this -> form = new BootstrapFormBuilder('form_busca_produto');
    $this -> form -> setFormTitle('Busca de Produtos');
    $this -> form -> setFieldSizes('100%');

    $nome = new TEntry('nome');
    $barcode = new TBarCodeInputReader('barcode');

    $this -> form -> addFields([new TLabel('Nome')], [$nome]);
    $this -> form -> addFields([new TLabel('Código de Barras')], [$barcode]);

    $this -> form -> addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search blue');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

    $this -> datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this -> datagrid -> width = '100%';

    $col_id = new TDataGridColumn('id', 'Id', 'left', '10%');
    $col_barcode = new TDataGridColumn('barcode', 'Código de Barras', 'left', '25%');
    $col_nome = new TDataGridColumn('nome', 'Nome', 'left', '30%');
    $col_categoria = new TDataGridColumn('Categoria->nome', 'Categoria', 'left', '15%');
    $col_estoque = new TDataGridColumn('estoque', 'Estoque', 'left', '10%');
    $col_preco = new TDataGridColumn('preco', 'Preço', 'left', '10%');

    $this -> datagrid -> addColumn($col_id);
    $this -> datagrid -> addColumn($col_barcode);
    $this -> datagrid -> addColumn($col_nome);
    $this -> datagrid -> addColumn($col_categoria);
    $this -> datagrid -> addColumn($col_estoque);
    $this -> datagrid -> addColumn($col_preco);

    $action1 = new TDataGridAction(['ProdutoForm', 'onEdit'], ['key' => '{id}']);
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

      $repository = new TRepository('Produtos');
      $limit = 10;

      if(empty($param['order'])){
        $param['order'] = 'id';
        $param['direction'] = 'asc';
      }

      $criteria = new TCriteria;
      $criteria -> setProperties($param);
      $criteria -> setProperty('limit', $limit);

      if(TSession::getValue('produto_filter')){
        $criteria -> add(TSession::getValue('produto_filter'));
      }
      
      $produtos = $repository -> load($criteria);

      $this -> datagrid -> clear();
      if($produtos){
        foreach($produtos as $produto){
          $this -> datagrid -> addItem($produto);
        }
      }

      $criteria -> resetProperties();
      $count = $repository -> count($criteria);
      
      $this -> pageNavigation -> setCount($count);
      $this -> pageNavigation -> setProperties($param);
      $this -> pageNavigation -> setLimit($limit);
      
      TTransaction::close();
      $this -> loaded = true;
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

      if($dados -> nome and $dados -> barcode){
        new TMessage('error', 'Informe apenas um dos campos de busca!');
      } else {
        if($dados -> nome){
          $criteria -> add(new TFilter('nome', 'like', "%{$dados -> nome}%"));
        } else if($dados -> barcode){
          $criteria -> add(new TFilter('barcode', 'like', "%{$dados -> barcode}%"));
        }
      }

      $repository = new TRepository('Produtos');
      $produtos = $repository -> load($criteria);
      
      if($produtos){
        foreach($produtos as $produto){
          $this -> datagrid -> addItem($produto);
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
    new TQuestion('Você realmente deseja excluir este produto?', $action);
  }

  public function Delete($param){
    try {
      TTransaction::open('geek');

      $key = $param['key'];
      $produto = new Produtos;
      $produto -> delete($key);

      $pos_action = new TAction([__CLASS__, 'onReload']);
      new TMessage('info', 'Produto excluído com sucesso!', $pos_action);
      
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