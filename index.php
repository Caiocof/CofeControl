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


//blog
$route->group("/blog");
$route->get("/", "Web:blog");
$route->get("/pg/{page}", "Web:blog");
$route->get("/{uri}", "Web:blogPost");
$route->post("/buscar", "Web:blogSearch");
$route->get("/buscar/{terms}/{page}", "Web:blogSearch");

//auth
$route->group(null);
$route->get("/entrar", "Web:login");
$route->get("/recuperar", "Web:forget");
$route->get("/cadastrar", "Web:register");

//optin
$route->get("/confirma", "Web:confirm");
$route->get("/obrigado", "Web:success");

//services
$route->get("/termos", "Web:terms");


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
if ($route->error()) {
    $route->redirect("/ooops/{$route->error()}");
}


//limpa o cach e entrega para o usuário
ob_end_flush();
