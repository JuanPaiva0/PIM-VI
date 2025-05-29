<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class ProdutoForm extends TPage{
  private $form;
  public function __construct(){
    parent::__construct();
    if(!PermissionHelper::accessEstoquista()){
      new TMessage('error', 'Acesso restrito: apenas estoquistas e supervisores podem acessar essa tela',
      new TAction(['Home', 'onReload']));
      exit;
    }
    $this -> form = new BootstrapFormBuilder('form_produto');
    $this -> form -> setFormTitle('Cadastro de Produtos');
    $this -> form -> setFieldSizes('100%');

    $id             = new TEntry('id');
    $nome           = new TEntry('nome');
    $barcode        = new TEntry('barcode');
    $categoria_id   = new TDBCombo('categoria_id', 'geek', 'Categorias', 'id', 'nome');
    $fabricante_id  = new TDBCombo('fabricante_id', 'geek', 'Fabricantes', 'id', 'nome');
    $plataforma     = new TEntry('plataforma');
    $prazo_garantia = new TEntry('prazoGarantia');
    $estoque        = new TEntry('estoque');
    $preco          = new TEntry('preco');

    $id -> setEditable(false);
    
    $row = $this -> form -> addFields([new TLabel('id'), $id],
                                      [new TLabel('Nome'), $nome],
                                      [new TLabel('Código de Barras'), $barcode]);
    $row -> layout = ['col-sm-1', 'col-sm-5', 'col-sm-6'];

    $row = $this -> form -> addFields([new TLabel('Categoria'), $categoria_id],
                                      [new TLabel('Fabricante'), $fabricante_id],
                                      [new TLabel('Plataforma'), $plataforma]);
    $row -> layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

    $row = $this -> form -> addFields([new TLabel('Prazo de Garantia (Dias)'), $prazo_garantia],
                                      [new TLabel('Estoque'), $estoque],
                                      [new TLabel('Preço'), $preco]);
    $row -> layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

    $this -> form -> addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this -> form -> addActionLink('Buscar', new TAction(['ProdutoBusca', 'onReload']), 'fa:search blue');
    
    parent::add($this -> form);
  }

  public function onSave($param){
    try {
      TTransaction::open('geek');

      $dados = $this -> form -> getData();
      $this -> form -> setData($dados);

      if(in_array($dados -> categoria_id, [1, 2])){
        if(empty($dados -> prazoGarantia)){
          throw new Exception('Fabricante não pode ser vazio!');
        }
        if(empty($dados -> plataforma)){
          throw new Exception('Plataforma não pode ser vazio!');
        }
        $dados -> prazoGarantia = (int)$dados -> prazoGarantia;
        
      } else {
        $dados -> prazoGarantia = null;
      }
      
      $produto = new Produtos;
      $produto -> fromArray((array) $dados);
      $produto -> store();

      new TMessage('info', 'Produto cadastrado com sucesso!');
      
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onReload($param){
    
  }

  public function onEdit($param){
    try {
      TTransaction::open('geek');

      if(isset($param['key'])){
        $id = $param['key'];
        $produto = new Produtos($id);
        $this -> form -> setData($produto);
      }

      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onClear(){
    $this -> form -> clear();
  }
}
?>