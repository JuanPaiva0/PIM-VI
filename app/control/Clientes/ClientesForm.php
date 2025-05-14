<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Menu\TMenu;
use Adianti\Wrapper\BootstrapFormBuilder;

class ClientesForm extends TPage{
private $form;
  public function __construct(){
    parent::__construct();

    $this -> form = new BootstrapFormBuilder('form_clientes');
    $this -> form -> setFormTitle('Cadastro de Clientes');
    $this -> form -> setFieldSizes('100%');

    $id            = new TEntry('id');
    $rg            = new TEntry('rg');
    $cpf           = new TEntry('cpf');
    $nome          = new TEntry('nome');
    $data_registro = new TDate('dataRegistro');
    $endereco      = new TEntry('endereco');
    $telefone      = new TEntry('telefone');
    $email         = new TEntry('email');

    $id -> setEditable(false);
    $rg -> setMask('99.999.999-9');
    $rg -> setMinLength(9);
    $rg -> addValidation('RG', new TRequiredValidator);
    $cpf -> setMask('999.999.999-99');
    $cpf -> addValidation('CPF', new TRequiredValidator);
    $nome -> addValidation('Nome', new TRequiredValidator);
    $data_registro -> setMask('dd/mm/yyyy');
    $data_registro -> setEditable(false);
    $data_registro -> setDatabaseMask('yyyy-mm-dd');
    $data_registro -> addValidation('Data de Registro', new TRequiredValidator);
    $endereco -> addValidation('Endereco', new TRequiredValidator);
    $telefone -> setMask('(99) 99999-9999');
    $telefone -> addValidation('Telefone', new TRequiredValidator);
    $email -> addValidation('Email', new TRequiredValidator);
    
    $row = $this -> form -> addFields( [new TLabel('Id'), $id],
                                       [new TLabel('Data de Registro'), $data_registro],
                                       [new TLabel('Nome'), $nome]);
    $row -> layout = ['col-sm-1', 'col-sm-4', 'col-sm-7'];

    $row = $this -> form -> addFields( [new TLabel('RG'), $rg],
                                       [new TLabel('CPF'), $cpf],
                                       [new TLabel('Telefone'), $telefone]);
    $row -> layout = ['col-sm-3', 'col-sm-3', 'col-sm-6'];

    $row = $this -> form -> addFields( [new TLabel('Email'), $email],
                                       [new TLabel('Endereco'), $endereco]);
    $row -> layout = ['col-sm-6', 'col-sm-6'];


    $this -> form -> addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this -> form -> addAction('Buscar', new TAction(['ClientesBusca', 'onReload']), 'fa:search blue');
    
    parent::add($this -> form);
  }

  public function onSave($param){
    try {
      TTransaction::open('geek');

      $dados = $this -> form -> getData();
      $this -> form -> setData($dados);

      $cliente = new Clientes;
      
      $cliente -> fromArray( (array) $dados );
      
      $cliente -> rg  = str_replace(['.', '-'], '', $cliente -> rg);
      $cliente -> cpf = str_replace(['.', '-'], '', $cliente -> cpf);
      $cliente -> telefone = str_replace(['(', ')', '-'], '', $cliente -> telefone);

      $idCliente = $cliente -> id ?? null;
      if($cliente -> existeRG($cliente -> rg, $idCliente)){
        throw new Exception('RG já cadastrado!');
      }

      $cliente -> dataRegistro = date('Y-m-d');
      $cliente -> store();
      
      new TMessage('info', 'Cliente cadastrado com sucesso!');
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onEdit($param){
    try {
      TTransaction::open('geek');
      if(isset($param['key'])){
        $id = $param['key'];
        $cliente = new Clientes($id);
        $this -> form -> setData($cliente);
      }
      
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onClear(){
    $this -> form -> clear();
  }
}
?>