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
$route->get("/em/{category}", "Web:blogCategory");
$route->get("/em/{category}/{page}", "Web:blogCategory");

//auth
$route->group(null);
$route->get("/entrar", "Web:login");
$route->post("/entrar", "Web:login");

$route->get("/cadastrar", "Web:register");
$route->post("/cadastrar", "Web:register");

$route->get("/recuperar", "Web:forget");
$route->post("/recuperar", "Web:forget");

$route->get("/recuperar/{code}", "Web:reset");
$route->post("/recuperar/resetar", "Web:reset");


//optin
$route->group(null);
$route->get("/confirma", "Web:confirm");
$route->get("/obrigado/{email}", "Web:success");

//services
$route->group(null);
$route->get("/termos", "Web:terms");


/**
 * APP
 */
$route->group("/app");
$route->get("/", "App:home");
$route->get("/receber", "App:income");
$route->get("/receber/{status}/{category}/{date}", "App:income");
$route->get("/pagar", "App:expense");
$route->get("/pagar/{status}/{category}/{date}", "App:expense");
$route->get("/fatura/{invoice}", "App:invoice");
$route->get("/perfil", "App:profile");
$route->get("/sair", "App:logout");

$route->post("/launch", "App:launch");
$route->post("/support", "App:support");
$route->post("/filter", "App:filter");

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
