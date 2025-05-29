<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapFormBuilder;

class GestaoLoginEdicao extends TPage{
  private $form;
   
  public function __construct(){
    parent::__construct();
    parent::setTargetContainer('adianti_right_panel');

    if(!PermissionHelper::accessSupervisor()){
      new TMessage('error', 'Acesso restrito: apenas supervisores podem acessar essa tela',
      new TAction(['Home', 'onReload']));
      exit;
    }

    $this -> form = new BootstrapFormBuilder('form_edit_login');
    $this -> form -> setFormTitle('Edição de Usuário');
    $this -> form -> setFieldSizes('100%');  

    $id           = new TEntry('id');
    $nome         = new TEntry('nome');
    $login        = new TEntry('login');
    $cargo        = new TDBCombo('cargo', 'geek', 'Cargos', 'id', 'cargo');
    $status       = new TCombo('status');
    $senha        = new TEntry('senha');
    $confim_senha = new TEntry('confirm_senha');
    
    $id -> setEditable(false);
    $nome -> setEditable(false);
    $cargo -> setEditable(false);
    $status -> addItems([1 => 'Ativo', 0 => 'Inativo']);
    
    $row = $this -> form -> addFields([new TLabel('Id'), $id],
                                      [new TLabel('Nome'), $nome],
                                      [new TLabel('Login'), $login],
                                      [new TLabel('Cargo'), $cargo]);
    $row -> layout = ['col-sm-1', 'col-sm-4', 'col-sm-4', 'col-sm-3'];

    $row = $this -> form -> addFields([new TLabel('Status'), $status],
                                      [new TLabel('Nova senha'), $senha],
                                      [new TLabel('Confirmar Senha'), $confim_senha]);
    $row -> layout = ['col-sm-2', 'col-sm-5', 'col-sm-5'];
    
    $btn = $this -> form -> addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save white');
    $btn -> class = 'btn btn-success';
    $this -> form -> addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this -> form -> addHeaderAction('Fechar', new TAction([$this, 'onClose']), 'fa:times red');
    parent::add($this -> form);
  }

  public function onSave($param){
    try {
      TTransaction::open('geek');
      $data =$this -> form -> getData();
      $this-> form-> setData($data); 

      $funcionario = new SystemUsers($data -> id);
      $funcionario -> login = $data -> login;
      $funcionario -> status = $data -> status;
      
      if(!empty($data -> senha)){
        if($data -> senha != $data -> confirm_senha){
          throw new Exception('As senhas informadas não são iguais!');
        }
        $funcionario -> password = password_hash($data -> senha, PASSWORD_DEFAULT);
      } 
      $funcionario -> store();

      new TMessage('info', 'Usuário atualizado com sucesso!');
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
        $funcionario = new SystemUsers($id);
        $this -> form -> setData($funcionario);
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

  public static function onClose($param){
    TScript::create("Template.closeRightPanel()");
     TScript::create("__adianti_load_page('index.php?class=GestaoLoginBusca&method=onReload');");
  }
}
?>