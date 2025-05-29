<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class FuncionarioForm extends TPage{
  private $form;
  public function __construct(){
    parent::__construct();
    if(!PermissionHelper::accessSupervisor()){
      new TMessage('error', 'Acesso restrito: apenas supervisores podem acessar essa tela',
      new TAction(['Home', 'onReload']));
      exit;
    }
    $this -> form = new BootstrapFormBuilder('form_funcionario');
    $this -> form -> setClientValidation(true);
    $this -> form -> setFormTitle('Cadastro de Funcionario');
    $this -> form -> setFieldSizes('100%');

    $id       = new TEntry('id');
    $rg       = new TEntry('rg');
    $cpf      = new TEntry('cpf');
    $nome     = new TEntry('nome');
    $endereco = new TEntry('endereco');
    $telefone = new TEntry('telefone');
    $email    = new TEntry('email');
    $cargo    = new TDBCombo('cargo_id', 'geek', 'Cargos', 'id', 'cargo');
    
    $id       -> setEditable(false);
    $rg       -> setMask('99.999.999-9');
    $rg       -> setMinLength(9);
    $rg       -> addValidation('RG', new TRequiredValidator);
    $cpf      -> setMask('999.999.999-99');
    $cpf      -> addValidation('CPF', new TRequiredValidator);
    $nome     -> addValidation('Nome', new TRequiredValidator);
    $endereco -> addValidation('Endereco', new TRequiredValidator);
    $telefone -> setMask('(99) 99999-9999');
    $telefone -> addValidation('Telefone', new TRequiredValidator);
    $email    -> addValidation('Email', new TRequiredValidator);
    $cargo    -> addValidation('Cargo', new TRequiredValidator);
    
    $row = $this -> form -> addFields( [new TLabel('Id'), $id],
                                       [new TLabel('RG'), $rg],
                                       [new TLabel('CPF'), $cpf]);
    $row -> layout = ['col-sm-1', 'col-sm-5', 'col-sm-6'];

    $row = $this -> form -> addFields( [new TLabel('Nome'), $nome],
                                       [new TLabel('Telefone'), $telefone],
                                       [new TLabel('Endereco'), $endereco]);
    $row -> layout = ['col-sm-3', 'col-sm-3', 'col-sm-6'];
    
    $row = $this -> form -> addFields( [new TLabel('Email'), $email],
                                       [new TLabel('Cargo'), $cargo]);
    $row -> layout = ['col-sm-7', 'col-sm-5'];

    $this -> form -> addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this -> form -> addActionLink('Buscar', new TAction(['FuncionarioBusca', 'onReload']), 'fa:search blue');    
    parent::add($this -> form);
  }

  public function onSave($param){
    try {
      TTransaction::open('geek');
      $dados = $this -> form -> getData();
      $this -> form -> setData($dados);
      
      $funcionario = new Funcionarios;

      $funcionario -> fromArray((array) $dados);
      $funcionario -> rg  = str_replace(['.', '-'], '', $funcionario -> rg);
      $funcionario -> cpf = str_replace(['.', '-'], '', $funcionario -> cpf);
      $funcionario -> telefone = str_replace(['(', ')', '-'], '', $funcionario -> telefone);

      $idFuncionario = $funcionario -> id ?? null;
      if($funcionario -> existeRG($funcionario -> rg, $idFuncionario)){
        throw new Exception('Usuário já cadastrado com esse RG!');
      }

      $funcionario -> store(); 
      new TMessage('info', 'Funcionario cadastrado com sucesso!');     
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onClear($param){
    $this -> form -> clear();
  }

  public function onEdit($param){
    try {
      TTransaction::open('geek');

      if(isset($param['key'])){
        $id = $param['key'];
        $funcionario = new Funcionarios($id);
        $this -> form -> setData($funcionario);
      }
      
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }  
}
?>