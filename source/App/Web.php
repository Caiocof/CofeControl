<?php


namespace Source\App;


use Source\Core\Controller;

/**
 * WEB CONTROLLER
 * @package Source\App
 */
class Web extends Controller
{
    /**
     * Web constructor.
     */
    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");
    }

    /**
     * SITE HOME
     */
    public function home(): void
    {
        echo $this->view->render("home", [
            "title" => "CaféControl - Gerencie sua contas com o melhor café"
        ]);
    }

    /*
     * SITE NAV ERROR
     * @param array $data
     */
    public function error(array $data): void
    {
        echo $this->view->render("error", [
            "title" => "{$data['errcode']} | Ooops!"
        ]);
    }

}