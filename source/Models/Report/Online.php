<?php


namespace Source\Models\Report;


use Source\Core\Model;
use Source\Core\Session;

/**
 * Class Online
 * @package Source\Models\Report
 */
class Online extends Model
{
    /** @var int */
    private $sessionTime;


    /**
     * Online constructor.
     * @param int $sessionTime
     */
    public function __construct(int $sessionTime = 20)
    {
        $this->sessionTime = $sessionTime;
        parent::__construct("report_online", ["id"], ["ip", "url", "agent"]);
    }


    /**METODO RESPONSAVEL POR BUSCAR OS REGISTROS ATIVOS
     * @param bool $count
     * @return array|int|null
     */
    public function findByActive(bool $count = false)
    {
        //busca no banco os registros que tenha a hora de atualização maior ou igual a hora atual no internalo de sessionTime
        $find = $this->find("updated_at >= NOW() - INTERVAL {$this->sessionTime} MINUTE");
        if ($count) {
            return $find->count();
        }

        return $find->fetch(true);
    }


    /**METODO RESPONSAVEL POR ALIMENTAR O BANCO
     * @return Online
     */
    public function report(bool $clear = true): Online
    {
        $session = new Session();
        if (!$session->has("online")) {
            $this->user = ($session->authUser ?? null);
            $this->url = (filter_input(INPUT_GET, "route", FILTER_SANITIZE_STRIPPED ?? "/"));
            $this->ip = filter_input(INPUT_SERVER, "REMOTE_ADDR");
            $this->agent = filter_input(INPUT_SERVER, "HTTP_USER_AGENT");

            $this->save();
            $session->set("online", $this->id);
            return $this;
        }

        //busca no banco a sessao pelo id
        $find = $this->findById($session->online);
        //se tiver a sessão mais não tiver o registo mata a sessão
        if (!$find) {
            $session->unset("online");
            return $this;
        }

        $find->user = ($session->authUser ?? null);
        $find->url = (filter_input(INPUT_GET, "route", FILTER_SANITIZE_STRIPPED ?? "/"));
        $find->pages += 1;

        $find->save();

        //verifica o status de clear passado na instancia do metodo
        if ($clear) {
            $this->clear();
        }

        return $this;

    }

    public function clear(): void
    {
        $this->delete("updated_at <= NOW() - INTERVAL {$this->sessionTime} MINUTE", null);
    }


    /**METODO RESPONSAVEL POR SALVAR OU ATUALIZAR DADOS DO BANCO
     * @return bool
     */
    public function save(): bool
    {
        /** UPDATE ACCESS */
        if (!empty($this->id)) {
            $onlineId = $this->id;
            $this->update($this->safe(), "id = :id", "id={$onlineId}");

            if ($this->fail) {
                $this->message->error("Erro ao atualizar, favor verificar os dados");
                return false;
            }

        }


        /** CREATED ACCESS */
        if (empty($this->id)) {
            $onlineId = $this->create($this->safe());

            if ($this->fail) {
                $this->message->error("Erro ao salvar, favor verificar os dados");
                return false;
            }
        }

        $this->data = $this->findById($onlineId)->data();
        return true;
    }

}