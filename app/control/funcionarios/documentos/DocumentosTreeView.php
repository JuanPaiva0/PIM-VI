<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TScroll;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TTreeView;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class DocumentosTreeView extends TPage{
  private $rootPath = 'app/documents/funcionarios';
  private $tree;
  private $form;
  
  public function __construct(){
    parent::__construct();
    if (!PermissionHelper::accessSupervisor()) {
      new TMessage('error', 'Acesso restrito: apenas supervisores podem acessar essa tela',
      new TAction(['Home', 'onReload']));
      exit;
    }

    $this -> form = new BootstrapFormBuilder('doc_funcionarios');
    $this -> form -> setFormTitle('Buscar diretórios');
    $this -> form -> setFieldSizes('100%');

    $funcionario = new TDBUniqueSearch('funcionario_id', 'geek', 'Funcionarios', 'id', 'nome');
    $funcionario->setMinLength(1);

    $this -> form -> addFields([new TLabel('Informe o funcionário')], [$funcionario]);
    $this -> form -> addHeaderActionLink('Novo diretório', new TAction(['NovoDiretorio', 'onReload']), 'fa:plus-circle green');
    $this -> form -> addHeaderAction('Inserir Arquivos', new TAction(['UploadDocuments', 'onReload']), 'fa:file fa-fw red');
    
    $this -> form -> addAction('Buscar', new TAction([__CLASS__, 'onSearch']), 'fa:search blue');
    $this -> form -> addAction('Limpar Treeview', new TAction([$this, 'limpaTreeview']), 'fa:eraser red');
    
    $this -> tree = new TTreeView('treeview');
    $this -> tree -> setSize('100%');
    $this -> tree -> collapse();
    $this -> tree -> style = 'margin: 10px';
    $this -> tree -> setItemAction(new TAction([$this, 'onSelect']));

    $scroll = new TScroll;
    $scroll -> setSize('100%', '400');
    $scroll -> style = 'margin: auto';
    $scroll -> add($this->tree);

    $vbox = new TVBox;
    $vbox -> style = 'width: 100%';
    $vbox -> add($this->form);
    $vbox -> add($scroll);

    $panel = new TPanelGroup;
    $panel -> add($vbox);
    parent::add($panel); 
  }

  public function onReload($param = null){
    if (!empty($param['funcionario_id'])) {
      try {
        TTransaction::open('geek');
        $funcionario = new Funcionarios($param['funcionario_id']);
        TTransaction::close();

        $estrutura = $this->mapearDiretorios($funcionario->caminho_documentos);
        $this->tree->fromArray([]); 
        $this->tree->fromArray($estrutura);
      } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
      }
    } else {
      $estrutura = $this->mapearDiretorios($this->rootPath);
      $this->tree->fromArray([]); 
      $this->tree->fromArray($estrutura);
    }
  }

  public function carregaTreeview($rootPath){
    $this -> tree -> fromArray([]);
    
    if (is_dir($rootPath)) {
      $estrutura = $this -> mapearDiretorios($rootPath);
      $this -> tree -> fromArray($estrutura);
    } else {
      new TMessage('error', 'O diretório de documentos não existe: ' . $rootPath);
    }
  }

  private function mapearDiretorios($path, $nivel = 0){
    $result = [];

    $itens = scandir($path);
    foreach($itens as $item){
      if($item === '.' || $item === '..')continue;
      
      $fullPath = $path . DIRECTORY_SEPARATOR . $item;

      if(is_dir($fullPath)){
        $result[$item] = $this -> mapearDiretorios($fullPath, $nivel + 1);
      } else {
        $result[$fullPath] = $item;
      }
    }
    return ($nivel == 0) ? [basename($path) => $result] : $result;
  }
  
  public function onSearch($param){
    $data = $this -> form -> getData();
    $this -> form -> setData($data);

    $this -> onReload($param);
  }

  public static function onSelect($param){
    $caminho = $param['key'];
    
    if(file_exists($caminho)){
      $url = 'download.php?file=' . urlencode($caminho);
      TScript::create("window.open('{$url}', '_blank');");
    } else {
      new TMessage('error', 'O arquivo selecionado não foi encontrado: ' . $caminho);
    }
  }
  
  public function limpaTreeview($param = null){
    $this -> onReload();
  }

  public function show(){
    parent::show();
  }
}
?>