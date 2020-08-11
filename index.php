<?php

//controlando o cach para garantir somente um output em toda a aplicação
ob_start();

require __DIR__ . "/vendor/autoload.php";

/**
 * BOOTSTRAP
 */

use Source\Core\Session;
use CoffeeCode\Router\Router;

$session = new Session();

//passsando o caminho atravez do nosso metodo de URL e o separador para a nossa rota
$route = new Router(url(), ":");

/**
 * WEB ROUTES
 */
$route->namespace("Source\App");
$route->get("/", "Web:home");
$route->get("/sobre", "Web:about");


/**
 * ERROR ROUTES
 */
$route->namespace("Source\App")->group("/ooops");//definimos o namespace e colocamos um grupo para os erros
$route->get("/{errcode}", "Web:error"); //passamos um codigo de erro que sera levado pelo controlador


/**METODO QUE DESPACHA AS ROTAS
 * ROUTE
 */
$route->dispatch();


/**CASO O DISPATCH NÃO CONSIGA TER SUCESSO E LANÇADO PARA CA
 * ERROR REDIRECT
 */
if ($route->error()){
    $route->redirect("/ooops/{$route->error()}");
}


//limpa o cach e entrega para o usuário
ob_end_flush();