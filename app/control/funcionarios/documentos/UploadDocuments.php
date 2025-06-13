<?php
use Adianti\Base\AdiantiFileSaveTrait;
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TMultiFile;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class UploadDocuments extends TPage{
  use AdiantiFileSaveTrait;
  
  private $form;
  public function __construct(){
    parent::__construct();
    parent::setTargetContainer('adianti_right_panel');
    
    $this -> form = new BootstrapFormBuilder('upload_form');
    $this -> form -> setFormTitle('Upload de Documentos');
    $this -> form -> setFieldSizes('100%');
    $this -> form -> setProperty('enctype', 'multipart/form-data');

    $funcionario = new TDBUniqueSearch('funcionario_id', 'geek', 'Funcionarios', 'id', 'nome');
    $subpasta = new TCombo('subpasta');
    $arquivo  = new TMultiFile('arquivo');

    $funcionario -> setMinLength(1);
    $funcionario -> setChangeAction(new TAction([$this, 'onCarregaSubpastas']));

    $arquivo -> enableFileHandling();
    $arquivo -> setAllowedExtensions(['pdf', 'jpg', 'png']);
   
    $this -> form->addFields([new TLabel('Funcionário')], [$funcionario]);
    $this -> form->addFields([new TLabel('Subpasta')], [$subpasta]);
    $this -> form->addFields([new TLabel('Arquivo')], [$arquivo]);
    $this -> form->addAction('Enviar', new TAction([$this, 'onUpload']), 'fa:upload green');
    $this -> form -> addHeaderAction('Voltar', new TAction([$this, 'onClose']), 'fa:arrow-circle-left red');
    
    parent::add($this -> form);
  }

  public static function onCarregaSubpastas($param){
    $id = $param['funcionario_id'];

    TTransaction::open('geek');
    $funcionario = new Funcionarios($id);
    $subpastas = $funcionario -> getSubpastas();
    TTransaction::close();

    TCombo::reload('upload_form', 'subpasta', $subpastas);
  }

  public function onUpload($param){
    try {
      TTransaction::open('geek');
      $data = $this -> form -> getData();
      $this -> form -> validate();
      
      $funcionario = new Funcionarios($data -> funcionario_id);
      $caminho = $funcionario -> caminho_documentos;
      $subpasta = $data -> subpasta;

      if(empty($subpasta)){
        throw new Exception('Seleciona uma subpasta para o upload!');
      }

      $destino = $caminho . DIRECTORY_SEPARATOR . $subpasta;

      if(!is_dir($destino)){
        throw new Exception('A subpasta ' . $data -> subpasta . 'não existe!');
      }

      if (!is_writable($destino)) {
        throw new Exception('A pasta de destino não tem permissão de escrita: ' . $destino);
      }

      $arquivos = (array) $data -> arquivo;
      $arquivosSalvos = [];


      foreach($arquivos as $arquivo){
        $fileData =  json_decode(urldecode($arquivo));

        if(empty($fileData->fileName) || !file_exists($fileData->fileName)){
          continue;
        }

        $nomeArquivo = $fileData -> fileName;
        $limpaNome = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($nomeArquivo));
        $caminhoCompleto = $destino . DIRECTORY_SEPARATOR . $limpaNome; 

        if(!rename($fileData->fileName, $caminhoCompleto)){
          throw new Exception('Erro ao mover o arquivo: ' . $nomeArquivo);
        }

        $arquivosSalvos[] = $nomeArquivo;
      }

      if(count($arquivosSalvos) > 0){
        new TMessage('info', 'Arquivos salvos com sucesso:<br>' . implode('<br>', $arquivosSalvos));
      } else {
        new TMessage('info', 'Nenhum arquivo foi enviado');
      }
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onReload(){
    
  }

  public function onClose($param){
    TScript::create("Template.closeRightPanel()");
    TScript::create("__adianti_load_page('index.php?class=DocumentosTreeView&method=onReload');");
  }
}
?>