<?php


namespace Source\Core;


use League\Plates\Engine;

/**
 * Class View
 * @package Source\Core
 */
class View
{
    /** @var Engine */
    private $engine;

    /**RESPONSAVEL POR CRIAR A NOSSA ENGINE
     * View constructor.
     * @param string $path
     * @param string $ext
     */
    public function __construct(string $path = CONF_VIEW_PATH, string $ext = CONF_VIEW_EXT)
    {
        $this->engine = Engine::create($path, $ext);

    }

    /**RESPONSAVEL POR ADICIONAL A PASTA RAIZ
     * @param string $name
     * @param string $path
     * @return View
     */
    public function path(string $name, string $path): View
    {
        $this->engine->addFolder($name, $path);
        return $this;
    }

    /**RESPONSVEL POR RENDEZIRAR O TEMPLATE COM OS DADOS
     * @param string $templateName
     * @param array $data
     * @return string
     */
    public function render(string $templateName, array $data): string
    {
        return $this->engine->render($templateName, $data);
    }

    /**RETORNA UMA ENGINE PARA MENIPULAÇÃO
     * @return Engine
     */
    public function engine(): Engine
    {
        return $this->engine();
    }
}