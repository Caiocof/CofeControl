<?php


namespace Source\Models\Report;


use Source\Core\Model;
use Source\Core\Session;

/**
 * Class Access
 * @package Source\Models\Report
 */
class Access extends Model
{

    /**
     * Access constructor.
     */
    public function __construct()
    {
        parent::__construct("report_access", ["id"], ["users", "views", "pages"]);
    }


    /**METODO RESPONSAVEL PELA ALIMENTAÇÃO DA TABELA
     * @return Access
     */
    public function report(): Access
    {

        //faz a consulta verificando se tem algum acesso na data de hoje
        $find = $this->find("DATE(created_at) = DATE(now())")->fetch();

        //cria uma session para as views
        $session = new Session();

        //verifica se teve algum relatorio do dia
        if (!$find) {
            $this->users = 1;
            $this->views = 1;
            $this->pages = 1;

            setcookie("access", true, time() + 86400, "/");
            $session->set("access", true);

            $this->save();
            return $this;
        }

        //caso o cookie do usuario não exista mais ele cria novamente e acresenta +1
        if (!filter_input(INPUT_COOKIE, "access")) {
            $find->users += 1;
            setcookie("access", true, time() + 86400, "/");
        }

        //caso a session não exista mais ele cria novamente e acresenta +1 na views
        if (!$session->has("access")) {
            $find->views += 1;
            $session->set("access", true);
        }

        $find->pages += 1;
        $find->save();
        return $this;

    }
}