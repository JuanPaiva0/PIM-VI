<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;
use Dompdf\Dompdf;

class CheckoutForm extends TPage{
  private $form;
  private $html;
  public function __construct(){
    parent::__construct();
    if(!PermissionHelper::accessAtendente()){
      new TMessage('error', 'Acesso restrito: apenas atendentes e supervisores podem acessar essa tela!',
      new TAction(['Home', 'onReload']));
      exit;
    }
    $this -> form = new BootstrapFormBuilder('form_checkout');
    $this -> form -> setFormTitle('Finalizar Venda');
    $this -> form -> setFieldSizes('100%');

    $funcionario     = new TDBUniqueSearch('funcionario', 'geek', 'Funcionarios', 'id', 'nome');
    $cliente         = new TDBUniqueSearch('cliente', 'geek', 'Clientes', 'id', 'cpf');
    $dataVenda       = new TDate('dataVenda');
    $valorTotal      = new TEntry('valorTotal');
    $formaPagamento  = new TDBCombo('formasPagamento', 'geek', 'FormasPagamento', 'id', 'forma');
    $statusPagamento = new TDBCombo('statusPagamento', 'geek', 'StatusPagamento', 'id', 'status');
    $statusVenda     = new TDBCombo('statusVenda', 'geek', 'StatusVenda', 'id', 'status');

    $funcionario -> setMinLength(1);
    $cliente     -> setMinLength(1);
    $dataVenda   -> setMask('dd/mm/yyyy');
    $dataVenda   -> setDatabaseMask('yyyy-mm-dd');
    $valorTotal  -> setMask('R$ 999.999,99');
    $valorTotal  -> setEditable(false);
    
    $cliente_id       = new TEntry('cliente_id');
    $cliente_nome     = new TEntry('cliente_nome');
    $cliente_email    = new TEntry('cliente_email');
    $cliente_telefone = new TEntry('cliente_telefone');
    $cliente_endereco = new TEntry('cliente_endereco');

    $cliente_id       -> setEditable(false);
    $cliente_nome     -> setEditable(false);
    $cliente_email    -> setEditable(false);
    $cliente_telefone -> setEditable(false);
    $cliente_endereco -> setEditable(false);

    $info_cliente = new BootstrapFormBuilder;
    $info_cliente -> setFieldSizes('100%');
    $info_cliente -> setProperty('style', 'border:none; background-color:#f8f9fa; padding:15px;  border-radius:10px');

    $row = $info_cliente->addFields([new TLabel('Id'), $cliente_id],
                                    [new TLabel('Nome'), $cliente_nome],
                                    [new TLabel('Email'), $cliente_email]);                                    
    $row->layout = ['col-sm-1', 'col-sm-5', 'col-sm-6'];
  
    $row = $info_cliente->addFields([new TLabel('Endereço'), $cliente_endereco],
                                    [new TLabel('Telefone'), $cliente_telefone]);                                    
    $row->layout = ['col-sm-8', 'col-sm-4'];

    
    $panel = new TPanelGroup('Informações do Cliente');
    $panel->add($info_cliente);
    $this -> form -> setFields([$cliente_id, $cliente_nome, $cliente_email, $cliente_telefone, $cliente_endereco]);
    $cliente -> setChangeAction(new TAction([$this, 'onClienteChange']));
    
    $row = $this -> form -> addFields([new TLabel('Funcionario'), $funcionario],
                                      [new TLabel('Cliente'), $cliente]); 
    $row -> layout = ['col-sm-6', 'col-sm-6'];

    $this -> form -> addContent([$panel]);
    
    $row = $this -> form -> addFields([new TLabel('Forma de Pagamento'), $formaPagamento],
                                      [new TLabel('Status Pagamento'), $statusPagamento],
                                      [new TLabel('Status Venda'), $statusVenda]);
    $row -> layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];
    
    $row = $this -> form -> addFields([new TLabel('Data da venda'), $dataVenda],
                                      [new TLabel('Valor Total'), $valorTotal]);
    $row -> layout = ['col-sm-6', 'col-sm-6'];

    $this -> form -> addHeaderAction('Novo Cliente', new TAction(['ClientesForm', 'onReload']), 'fa:plus green');
    $this -> form -> addHeaderAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this -> form -> addHeaderAction('Concluir', new TAction([$this, 'onConcluir']), 'fa:save blue');

    parent::add($this -> form);
  }

  public static function onClienteChange($param){
    try {
      if(!empty($param['cliente'])){
        TTransaction::open('geek');
        $cliente = new Clientes((int) $param['cliente']);
        TTransaction::close();

        $form_data = new stdClass;
        $form_data -> cliente_id       = $cliente -> id;
        $form_data -> cliente_nome     = $cliente -> nome;
        $form_data -> cliente_email    = $cliente -> email;
        $form_data -> cliente_telefone = $cliente -> telefone;
        $form_data -> cliente_endereco = $cliente -> endereco;

        TForm::sendData('form_checkout', $form_data);
      }
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onReload($param){
   try { 
    $carrinho = TSession::getValue('carrinho_produtos'); 
    if(!$carrinho || empty($carrinho)){
      throw new Exception('Carrinho vazio!');
    } 

    $total = 0;

    foreach($carrinho as $produto){
      $total += $produto -> preco * $produto -> quantidade;
    }

    $form_data = new stdClass;
    $form_data -> valorTotal = $total;

    TForm::sendData('form_checkout', $form_data);
   } catch (Exception $e) {
    new TMessage('error', $e -> getMessage());
    AdiantiCoreApplication::loadPage('VendasForm', 'onReload');
   }
  }

  public function onConcluir($param){
    try {
      TTransaction::open('geek');

      $dados = $this -> form -> getData();
      $this -> form ->setData($dados);

      $dados->valorTotal = str_replace(['R$', '.', ','], ['', '', '.'], $dados->valorTotal);
      $registro_venda = new Vendas();
      $produtos = TSession::getValue('carrinho_produtos');

      $registro_venda -> funcionario_id  = $dados -> funcionario;
      $registro_venda -> cliente_id      = $dados -> cliente;
      $registro_venda -> dataVenda       = $dados -> dataVenda;
      $registro_venda -> valorTotal      = $dados -> valorTotal;
      $registro_venda -> formaPagamento  = $dados -> formasPagamento;
      $registro_venda -> statusPagamento = $dados -> statusPagamento;
      $registro_venda -> statusVenda     = $dados -> statusVenda;
      $registro_venda ->store();

      foreach($produtos as $produto){
        $estoque = new Produtos($produto->id);
        $estoque -> quantidade -= $produto -> quantidade;

        $item = new ItensVenda();
        $item -> venda_id   = $registro_venda -> id;
        $item -> produto_id = $produto -> id;
        $item -> quantidade = $produto -> quantidade;
        $item -> subtotal   = $produto -> quantidade * $produto -> preco;
        $item -> store();
      }

      $this -> onGeraPDF($dados);
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onGeraPDF($dados){
    try {
      TTransaction::open('geek');
      $produtos = TSession::getValue('carrinho_produtos');

      $this -> html = new THtmlRenderer('app/resources/checkout.html');

      $total = 0;
      $itens = [];
      
      foreach($produtos as $produto){
        $subtotal = $produto -> preco * $produto -> quantidade;
        $total += $subtotal;

        $itens[] = [
          'nome'       => $produto->nome,
          'quantidade' => $produto->quantidade,
          'preco'      => number_format($produto->preco, 2, ',', '.'),
          'subtotal'   => number_format($subtotal, 2, ',', '.'),
        ];
      }

      $cliente         = new Clientes($dados -> cliente);
      $funcionario     = new Funcionarios($dados -> funcionario);
      $forma_pagamento = new FormasPagamento($dados -> formasPagamento);

      $replace = [
        'funcionario_nome' => $funcionario->nome,
        'cliente_nome'     => $cliente->nome,
        'cliente_cpf'      => $cliente->cpf,
        'itens'            => $itens,
        'data'             => $dados->dataVenda,
        'forma_pagamento'  => $forma_pagamento->forma,
        'total'            => number_format($total, 2, ',', '.')
      ];
      
      $this -> html -> enableSection('main', $replace);
      TTransaction::close();
      
      $contents = $this -> html -> getContents();

      $options = new \Dompdf\Options();
      $options -> setIsRemoteEnabled(true);
      $options -> setChroot(getcwd());

      $dompdf = new \Dompdf\Dompdf($options);
      $dompdf -> loadHtml($contents);
      $dompdf -> setPaper('A4', 'portrait');
      $dompdf -> render();

      file_put_contents('app/output/checkout_resumo.pdf', $dompdf->output());

      $window = TWindow::create('Resumo da Compra', 0.8, 0.8);
      $obj = new TElement('object');
      $obj->data  = 'app/output/checkout_resumo.pdf';
      $obj->type  = 'application/pdf';
      $obj->style = "width: 100%; height:calc(100% - 10px)";
      $obj->add('Seu navegador não suporta exibir PDF. <a href="'.$obj->data.'" target="_blank">Clique aqui para baixar.</a>');

      $window->add($obj);
      $window->show();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onClear(){
    $this -> form -> clear();
  }
}
?>