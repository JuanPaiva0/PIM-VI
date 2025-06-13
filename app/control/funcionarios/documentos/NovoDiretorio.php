<?php

use Adianti\Control\TAction;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class NovoDiretorio extends TWindow{
  private $form;
  public function __construct(){
    parent::__construct();
    parent::setTitle('Novo diretório');
    parent::setSize(0.6, null);

    $this->form = new BootstrapFormBuilder('form_novo_diretorio');
    $this->form->setFieldSizes('100%');

    $funcionario = new TDBUniqueSearch('funcionario_id', 'geek', 'Funcionarios', 'id', 'nome');
    $nomeDir = new TEntry('nome_diretorio');
    
    $funcionario->setMinLength(1);  

    $this->form->addFields([new TLabel('Informe o funcionário')], [$funcionario]);
    $this->form->addFields([new TLabel('Informe o nome do diretório')], [$nomeDir]);
    $this -> form -> addAction('Criar diretório', new TAction([$this, 'makeDir']), 'fa:plus-circle green');
    

    parent::add($this -> form);
  }

  public function makeDir($param){
    try {
      TTransaction::open('geek');
      $data = $this -> form -> getData();

      $funcionario = new Funcionarios($data -> funcionario_id);
      $caminho = $funcionario -> caminho_documentos;

      if(empty($data -> nome_diretorio)){
        throw new Exception('Informe o nome do diretório');
      }

      $funcionario = new Funcionarios($data -> funcionario_id);
      $caminho = $funcionario -> caminho_documentos;

      $nomeSemAcento = $this -> tirarAcentos($data -> nome_diretorio);
      $nomeLimpo = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nomeSemAcento);
      $nomeLimpo = preg_replace('/_+/', '_', $nomeLimpo);
      $nomeLimpo = trim($nomeLimpo, '_');

      $caminhoCompleto = $caminho . DIRECTORY_SEPARATOR . basename($nomeLimpo);

      if(is_dir($caminhoCompleto)){
        throw new Exception('Diretório já existe: ' . $caminhoCompleto);
      }

      if(!mkdir($caminhoCompleto, 0755, true)){
        throw new Exception('Erro ao criar diretório: ' . $caminhoCompleto);
      }

      new TMessage('info', 'Diretório criado com sucesso: ' . $caminhoCompleto, new TAction(['DocumentosTreeView', 'onReload'], ['funcionario_id' => $data->funcionario_id]));
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e -> getMessage());
    }
  }

  public function onReload(){
    
  }

  function tirarAcentos($string){
    return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"), $string);
  }
}
?>