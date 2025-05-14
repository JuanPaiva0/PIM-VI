<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TBarCodeInputReader;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class VendasForm extends TPage{
 private $form;
 private $datagrid;
 private $pageNavigation;
 private $loaded;
 private $total;
 
  public function __construct(){
    parent::__construct();

    $this -> form = new BootstrapFormBuilder('form_Vendas');
    $this -> form -> setFormTitle('Vendas');
    $this -> form -> setFieldSizes('100%');

    $nome = new TDBUniqueSearch('nome','geek', 'Produtos', 'id', 'nome');
    $barcode = new TBarCodeInputReader('barcode');
    $quantidade = new TEntry('quantidade');

    $nome -> setMinLength(1);

    $this -> form -> addFields([new TLabel('Descrição'), $nome]);
    $this -> form -> addFields([new TLabel('Código de Barras'), $barcode]);
    $this -> form -> addFields([new TLabel('Quantidade'), $quantidade]);

    $this -> form -> addAction('Inserir', new TAction([$this, 'onInsert']), 'fa:plus green');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

    $this -> datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this -> datagrid -> width = '100%';
    
    $col_id         = new TDataGridColumn('id', 'Id', 'left', '5%');
    $col_nome       = new TDataGridColumn('nome', 'Nome', 'left', '45%');
    $col_quantidade = new TDataGridColumn('quantidade', 'Quantidade', 'left', '10%'); 
    $col_preco      = new TDataGridColumn('preco', 'Preço', 'left', '25%');
    $col_total      = new TDataGridColumn('= {quantidade} * {preco}', 'Total', 'left', '15%');

    $this -> datagrid -> addColumn($col_id);
    $this -> datagrid -> addColumn($col_nome);
    $this -> datagrid -> addColumn($col_quantidade);
    $this -> datagrid -> addColumn($col_preco);
    $this -> datagrid -> addColumn($col_total);

    $format_valor = function($value){
      if(is_numeric($value)){
        return 'R$ ' . number_format($value, 2, ',', '.');
      }
    };

    $col_preco -> setTransformer($format_valor);
    $col_total -> setTransformer($format_valor);

    $col_total -> setTotalFunction(function($values){
      return array_sum((array)$values);
    });

    $action1 = new TDataGridAction(['VendasForm', 'onDelete'], ['key' => '{id}']);
    $action2 = new TDataGridAction(['VendasForm', 'onEdit'], ['key' => '{id}', 'quantidade' => '{quantidade}']);
    
    $this -> datagrid -> addAction($action2, 'Editar', 'fa:edit blue');
    $this -> datagrid -> addAction($action1, 'Excluir', 'fa:trash red');
    
    $this -> datagrid -> createModel();

    $this -> pageNavigation = new TPageNavigation;
    $this -> pageNavigation -> setAction(new TAction([$this, 'onReload']));

    $this -> total = new TLabel('0,00');
    $this -> total -> setFontStyle('b');

    $panel = new TPanelGroup;
    $panel -> add($this -> datagrid);
    $panel -> add(new TLabel('Total: '));
    $panel -> add($this -> total);
    $panel -> addHeaderActionLink('Finalizar', new TAction(['CheckoutForm', 'onReload']), 'fa:check green');
    $panel -> addHeaderActionLink('PDF', new TAction([$this, 'onPDF']), 'fa:file-pdf red');

    
    $hbox = new THBox;
    $hbox -> add($this -> form) -> style.='vertical-align: top; width:40%; margin-right: 20px;';
    $hbox -> add($panel) -> style.='vertical-align: top; width:55%;';
    
    parent::add($hbox);
  }

  public function onPDF($param){
    try {
      $html = clone $this -> datagrid;
      $conteudo = file_get_contents('app/resources/styles-print.html') . $html -> getContents();

      $dompdf = new \Dompdf\Dompdf();
      $dompdf -> loadHtml($conteudo);
      $dompdf -> setPaper('A4', 'portrait');
      $dompdf -> render();  

      $file = 'app/output/vendas.pdf';

      file_put_contents($file, $dompdf -> output());

      $window = TWindow::create('PDF', 0.8, 0.8);

      $obj = new TElement('object');
      $obj -> data = $file;
      $obj -> type = 'application/pdf';
      $obj -> style = 'width: 100%; height: calc(100vh - 10px);';

      $window -> add($obj);
      $window -> show();
      
    } catch (\Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onEdit($param){
    try {
      TTransaction::open('geek');
      $id = $param['key'];
      $quantidade = $param['quantidade'];

      $produto = new stdClass;
      $produto -> nome = $id;
      $produto -> quantidade = $quantidade;

      TSession::setValue('editando_produto', $id);
      TForm::sendData('form_Vendas', $produto);
      
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onReload($param){
    try {
      TTransaction::open('geek');

      $repository = new TRepository('Produtos');
      $limit = 10;
      
      $criteria = new TCriteria;
      $criteria -> setProperties($param);
      $criteria -> setProperty('limit', $limit);
      $criteria -> setProperty('order', 'nome');

      if(TSession::getValue('filtro_produtos')){
        $criteria -> add(TSession::getValue('filtro_produtos'));
      }

      $produtos = $repository -> load($criteria);
      $this -> datagrid -> clear();
      if($produtos){
        foreach($produtos as $produto){
          $this -> datagrid -> addItem($produto);
        }
      }

      $this -> datagrid -> clear();

      $carrinho_produtos = TSession::getValue('carrinho_produtos');
      $total = 0;
      if($carrinho_produtos){
        foreach($carrinho_produtos as $objeto){
          $this -> datagrid -> addItem($objeto);
          $total += $objeto -> preco * $objeto -> quantidade;
        }
      }
      $this->total->setValue(number_format($total, 2, ',', '.'));
            
      TTransaction::close();
      $this -> loaded = true;
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onInsert($param){
    try {
     TTransaction::open('geek');
      $dados = $this -> form -> getData();

      if($dados -> nome and $dados -> barcode){
        throw new Exception('Informe apenas um campo de busca!');
      } else {
        if($dados -> nome){
          $criteria = new TCriteria;
          $criteria -> add(new TFilter('id', '=', $dados -> nome));
        } else if($dados -> barcode){
          $criteria = new TCriteria;
          $criteria -> add(new TFilter('barcode', '=', $dados -> barcode));
        } else {
          throw new Exception('Informe um campo de busca!');
        }
      }

      if($dados -> quantidade <= 0){
        throw new Exception('Informe uma quantidade válida!');
      }
      
      $repository = new TRepository('Produtos');
      $produtos = $repository -> load($criteria);

      if($produtos){
        $produto = $produtos[0];
        $carrinho_produtos = TSession::getValue('carrinho_produtos') ?: [];

        $produto_existe = false;
        $editando_id = TSession::getValue('editando_produto');
        
        foreach($carrinho_produtos as $key => $item){
          if($item -> id ==$produto -> id){
            if($editando_id && $editando_id == $item -> id ){
              $carrinho_produtos[$key] -> quantidade = $dados -> quantidade;
              $produto_existe = true;
              break;
            } else {
              $carrinho_produtos[$key] -> quantidade += $dados -> quantidade;
              $produto_existe = true;
              break;
            }
          }
        }
        if(!$produto_existe){
          $produto -> quantidade = $dados -> quantidade;
          $carrinho_produtos[] = $produto;
        }

        TSession::setValue('editando_produto', null);
        TSession::setValue('carrinho_produtos', $carrinho_produtos);
        $this -> onReload(func_get_arg(0));
      }
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onDelete($param){
    try {
      $carrinho_produtos = TSession::getValue('carrinho_produtos');
      
      foreach($carrinho_produtos as $key => $item){
        if($item -> id == $param['key']){
          unset($carrinho_produtos[$key]);
          break;
        }
      }
      $carrinho = array_values($carrinho_produtos);
      TSession::setValue('carrinho_produtos', $carrinho);
      $this -> onReload($param);
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onClear(){
    $this -> form -> clear();
  }

  public function show(){
    if(!$this -> loaded){
      $this -> onReload( func_get_arg(0) ); 
    }
    parent::show();
  }
}
?>