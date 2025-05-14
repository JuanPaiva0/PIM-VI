<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Wrapper\BootstrapFormBuilder;

class GestaoLoginForm extends TPage{
  private $form;
  private $funcionario_list;
  public function __construct(){
    parent::__construct();

    $this -> form = new BootstrapFormBuilder('gestao_login_form');
    $this -> form -> setFormTitle('Gestão de Login');
    $this -> form ->setFieldSizes('100%');

    $func_id    = new TEntry('id');
    $func_nome  = new TEntry('nome');
    $func_login = new TEntry('login');
    $func_senha = new TPassword('password');
    $func_confirm_senha = new TPassword('confirm_pass');

    $func_id   -> setEditable(false);
    $func_nome -> setEditable(false);

    $row = $this -> form -> addFields([new TLabel('Id'), $func_id],
                                      [new TLabel('Nome'), $func_nome]);
    $row -> layout = ['col-sm-2', 'col-sm-10'];

    $row = $this->form->addFields([new TLabel('Login'), $func_login],
                                  [new TLabel('Senha'), $func_senha],
                                  [new TLabel('Confirmar Senha'), $func_confirm_senha]);
    $row->layout = ['col-sm-6', 'col-sm-3', 'col-sm-3'];

    $this -> form -> addAction('Cadastrar', new TAction([$this, 'onCadastrar']), 'fa:plus-circle green');
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

    $nome_search  = new TEntry('nome_search');
    $cargo_search = new TEntry('cargo_search');
    $nome_search  -> placeholder = 'Nome';
    $cargo_search -> placeholder = 'Cargo';

    $this -> funcionario_list = new TCheckList('funcionario_list');
    $this -> funcionario_list -> setHeight(250);
    $this -> funcionario_list -> makeScrollable();
    $this -> funcionario_list -> setSelectAction(new TAction([$this, 'onSelect']));

    $this -> funcionario_list -> addColumn('id', 'Id', 'center', '10%');
    $this -> funcionario_list -> addColumn('nome', 'Nome', 'left', '45%');
    $this -> funcionario_list -> addColumn('cargo', 'Cargo', 'left', '45%');

    $hbox = new THBox;
    $hbox->style = 'border-bottom: 1px solid gray;padding-bottom:10px';
    $hbox -> add(new TLabel('Usuário sem login'));
    $hbox->add($nome_search)->style = 'float:right;width:30%;';
    $hbox->add($cargo_search)->style = 'float:right;width:30%;margin-right:10px;';

    $this -> carregaFuncionarioSemLogin(); 

    $panel = new TPanelGroup;
    $panel -> add($this -> form);
    $panel -> add($hbox);
    $panel -> add($this -> funcionario_list);
    
    parent::add($panel);
  }

  public function onCadastrar($param){
    try {
      TTransaction::open('geek');
      $dados = $this -> form -> getData();

      if(empty($dados->id)){
        throw new Exception('Usuário sem identificação!');
      }

      if (empty($dados->login) || empty($dados->password) || empty($dados->confirm_pass)) {
            throw new Exception('Preencha todos os campos obrigatórios.');
        }

      if ($dados->password !== $dados->confirm_pass) {
          throw new Exception('As senhas não coincidem.');
      }
      
      $existing = SystemUsers::where('login', '=', $dados->login)->first();
      if ($existing) {
          throw new Exception('Este login já está em uso. Escolha outro.');
      }
  
      $funcionario = new Funcionarios($dados->id);
      $system_users = new SystemUsers;
      
      $system_users -> nome           = $funcionario -> nome;
      $system_users -> login          = $dados -> login;
      $system_users -> password         = password_hash($dados->password, PASSWORD_DEFAULT);
      $system_users -> email          = $funcionario -> email;
      $system_users -> cargo          = $funcionario -> cargo_id;
      $system_users -> funcionario_id = $funcionario -> id;
      $system_users -> status         = true;
      $system_users -> store();

      new TMessage('info', 'Login criado com sucesso');

      $this -> carregaFuncionarioSemLogin();
      
      TTransaction::close();
    } catch (Exception $e) {
      TTransaction::rollback();
      new TMessage('error', $e -> getMessage());
    }
  }

  public function carregaFuncionarioSemLogin(){
    try {
      TTransaction::open('geek');
     
      $logins = SystemUsers::all();
      $ids_login = array_map(fn($user) => $user -> funcionario_id, $logins);

      $criteria = new TCriteria;
      if($ids_login){
        $criteria -> add(new TFilter('id', 'NOT IN', $ids_login));
      }

      $repository = new TRepository('Funcionarios');
      $funcionarios = $repository -> load($criteria);

      $lista = [];
      foreach($funcionarios as $f){
        $obj = new stdClass;
        $obj -> id = $f->id;
        $obj -> nome = $f->nome;
        $obj -> cargo =$f->cargo->cargo;

        $lista[] = $obj;
      }

      $this -> funcionario_list ->addItems($lista);
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public static function onSelect($param){
     try {
      TTransaction::open('geek');

      $selected_ids = $param['funcionario_list'] ?? [];

      if(!empty($selected_ids)){
        $id = $selected_ids[0];

        $funcionario = new Funcionarios($id);

        $form_data = new stdClass;
        $form_data->id       = $funcionario->id;
        $form_data->nome     = $funcionario->nome;
        $form_data->login    = $funcionario->email;
        $form_data->password = '';

        TForm::sendData('gestao_login_form', $form_data);
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