<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;

class Home extends TPage
{
  public function __construct()
  {
    parent::__construct();
    $panel = new TPanelGroup();

    $container = new TElement('div');
    $container->style = 'width: 100%; height: 75vh; display: flex; justify-content: center; align-items: center;';

    $cardsContainer = new TElement('div');
    $cardsContainer->style = 'display: flex; flex-wrap: wrap; gap: 50px; justify-content: center; padding: 20px; max-width: 1200px;';

    $cardsContainer->add($this->createCard(
      'carrinho-compras.png',
      'Nova Venda',
      'Acessar PDV',
      new TAction(['VendasForm', 'onReload'])
    ));

    $cardsContainer->add($this->createCard(
      'cliente.png',
      'Clientes',
      'Gerenciar cadastros',
      new TAction(['ClientesBusca', 'onReload'])
    ));

    $cardsContainer->add($this->createCard(
      'caixa.png',
      'Produtos',
      'Controle de estoque',
      new TAction(['ProdutoBusca', 'onReload'])
    ));

    $container->add($cardsContainer);
    $panel->add($container);
    parent::add($panel);
  }

  private function createCard($imagem, $titulo, $descricao, $action)
  {
    $card = new TElement('div');
    $card->style = 'width: 280px; padding: 25px; background: linear-gradient(145deg, #cbcbcb, #f1f1f1); border-radius: 20px; 
                    box-shadow:  28px 28px 56px #a5a5a5, -28px -28px 56px #dfdfdf;
                    cursor: pointer; text-align: center;
                    transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center;';

    $card->onclick = "__adianti_load_page('{$action->serialize()}')";

    $img = new TElement('img');
    $img->src = 'app/images/' . $imagem;
    $img->style = 'width: 80px; height: 80px; object-fit: contain; margin-bottom: 20px;';
    $card->add($img);

    $title = new TElement('h3');
    $title->style = 'margin: 0; color: #2c3e50; font-size: 1.2em; font-weight: 600; margin-bottom: 8px;';
    $title->add($titulo);
    $card->add($title);

    $desc = new TElement('p');
    $desc->style = 'color: #7f8c8d; margin: 0; font-size: 0.9em;';
    $desc->add($descricao);
    $card->add($desc);

    $card->onmouseover = "this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.15)'";
    $card->onmouseout = "this.style.transform=''; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'";

    return $card;
  }
}