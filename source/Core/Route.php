<?php


namespace Source\Core;


class Route
{
    protected static $route;

    public static function get(string $route, $handler): void
    {
        //pegando a URL
        $get = "/" . filter_input(INPUT_GET, "url", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        //fazendo a composição da nossa rota
        self::$route = [
            $route => [
                "route" => $route,
                //verifica se o handler e uma string, se não for repassa ele, se for pega tudo que tem antes dos ':'
                // (ultimo argumento como "true" antes, como "false" depois)
                "controller" => (!is_string($handler) ? $handler : strstr($handler, ":", true)),

                //verifica se o handler e uma string, se não for, não tem metodo, se for subustritui os ':' por nada
                //depois pega tudo que tem depois dos ':'
                // (ultimo argumento como "true" antes, como "false" depois)
                "method" => (!is_string($handler) ?: str_replace(":", "", strstr($handler, ":", false)))
            ]
        ];

        //enviando a nossa rota
        self::dispatch($get);
    }

    public static function dispatch($route): void
    {
        //verifica se a rota existe, se existir passa ela se não passa um array vazio
        $route = (self::$route[$route] ?? []);

        //se existir uma rota
        if ($route) {
            //se essa rota for uma Closure invocamos a propria
            if ($route['controller'] instanceof \Closure) {
                call_user_func($route['controller']);
                return;
            }

            //pegando o caminho do namespace e concatenando com o controle que preciso
            $controller = self::namespace() . $route['controller'];
            //pegando o metodo atravez da variavel
            $method = $route['method'];

            //verifica se a classe controle existe
            if (class_exists($controller)){
                //atribui ele a uma nova variavel
                $newController = new $controller;

                //verifica se existe o metodo dentro daquele controller
                if (method_exists($controller, $method)){
                    $newController->$method();
                }
            }
        }

    }

    public static function routes(): array
    {
        return self::$route;
    }

    private static function namespace(): string
    {
        return "Source\App\Controllers\\";
    }

}