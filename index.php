<?php
use Adianti\Control\TPage;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiTemplateParser;
use Adianti\Registry\TSession;

require_once 'init.php';

$ini    = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
$class  = $_REQUEST['class'] ?? '';
$method = $_REQUEST['method'] ?? null;

new TSession;

$is_logged = TSession::getValue('user');

$load_login_layout = (!$class && !$is_logged) || $class === 'LoginForm';

if ($load_login_layout) {
    $content = file_get_contents("app/templates/{$theme}/login.html");
    $content = ApplicationTranslator::translateTemplate($content);
    $content = AdiantiTemplateParser::parse($content);
} else {
    $content     = file_get_contents("app/templates/{$theme}/layout.html");
    $menu_string = AdiantiMenuBuilder::parse('menu.xml', $theme);
    $content     = ApplicationTranslator::translateTemplate($content);
    $content     = str_replace('{LIBRARIES}', file_get_contents("app/templates/{$theme}/libraries.html"), $content);
    $content     = str_replace('{class}', $class, $content);
    $content     = str_replace('{template}', $theme, $content);
    $content     = str_replace('{MENU}', $menu_string, $content);
    $content     = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top-public.xml', $theme), $content);
    $content     = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom-public.xml', $theme), $content);
    $content     = str_replace('{lang}', $ini['general']['language'], $content);
    $content     = str_replace('{title}', $ini['general']['title'] ?? '', $content);
    $content     = str_replace('{template_options}', json_encode($ini['template'] ?? []), $content);
    $content     = str_replace('{adianti_options}', json_encode($ini['general']), $content);

    $css         = TPage::getLoadedCSS();
    $js          = TPage::getLoadedJS();
    $content     = str_replace('{HEAD}', $css . $js, $content);
}

echo $content;

if ($class) {
    AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
} else {
    if ($is_logged) {
        AdiantiCoreApplication::loadPage('Home');
    } else {
        AdiantiCoreApplication::loadPage('LoginForm');
    }
}