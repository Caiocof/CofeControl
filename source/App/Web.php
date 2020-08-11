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
        parent::__construct(__DIR__ . "/../../views/" . CONF_VIEW_THEME . "/");
    }

    /**
     * SITE HOME
     */
    public function home(): void
    {

    }

    /*
     * SITE NAV ERROR
     * @param array $data
     */
    public function error(array $data): void
    {

    }

}