<?php


namespace Source\Core;


use Source\Support\Message;

/**
 * Class Model
 * @package Source\Models
 */
abstract class Model
{
    /**@var object|null */
    protected $data;

    /**@var \PDOException|null */
    protected $fail;

    /** @var Message|null */
    protected $message;

    /**AO CONSTRUIR UM OBJETO QUE EXTEND MODEL AUTOMATICAMENTE O MESSAGE E INSTANCIADO
     * Model constructor.
     */
    public function __construct()
    {
        $this->message = new Message();
    }

    //responsavel por tratar os atributos, caso eles já existam traz de data
    //se não existir joga em data
    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (empty($this->data)) {
            $this->data = new \stdClass();
        }
        $this->data->$name = $value;
    }

    //manipulando para buscar o valor dentro de data

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data->$name);
    }

    //mudando o comportado de quando chamar uma propriedade que não exista
    //ou não esta acessivel.
    /**
     * @param $name
     * @return |null
     */
    public function __get($name)
    {
        return ($this->data->$name ?? null);
    }


    /**
     * @return object|null
     */
    public function data(): ?object
    {
        return $this->data;
    }

    /**
     * @return \PDOException|null
     */
    public function fail(): ?\PDOException
    {
        return $this->fail;
    }

    /**
     * @return Message|null
     */
    public function message(): ?Message
    {
        return $this->message;
    }

    /**
     * @param string $entity
     * @param array $data
     * @return int|null
     */
    protected function create(string $entity, array $data): ?int
    {
        try {
            //contrução das colunas para o DB
            $columns = implode(", ", array_keys($data));

            //construção dos dados para o DB
            //colocamos os : no inico e o separador vai ter ', :' para ter como referencia
            $values = ":" . implode(", :", array_keys($data));

            //preparando a query
            $stmt = Connect::getInstance()->prepare("INSERT INTO {$entity} ({$columns}) VALUES ({$values})");
            //execultando a query com o filtro dos dados
            $stmt->execute($this->filter($data));

            //retornando o ID do usuario cadastrado
            return Connect::getInstance()->lastInsertId();

        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @param string $select
     * @param string|null $params
     * @return \PDOStatement|null
     */
    protected function read(string $select, string $params = null): ?\PDOStatement
    {
        try {
            $stmt = Connect::getInstance()->prepare($select);

            //verifica se tem algo em params, se tiver faz o bind para transformar em int
            if ($params) {
                parse_str($params, $params);

                foreach ($params as $key => $value) {
                    //verifica se o parametro passado pela key e uma palavra reservada limit ou offset
                    //se for trasnforma o key em um int
                    if ($key == 'limit' || $key == 'offset') {
                        $stmt->bindValue(":{$key}", $value, \PDO::PARAM_INT);
                    } else {
                        //caso não seja nenhuma das palavras reservadas retorna uma string
                        $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
                    }

                }
            }

            $stmt->execute();
            return $stmt;
        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }

    /**
     * @param string $entity
     * @param array $data
     * @param string $terms
     * @param string $params
     * @return int|null
     */
    protected function update(string $entity, array $data, string $terms, string $params): ?int
    {
        try {
            //montando o que sera setado
            $dataSet = [];
            foreach ($data as $bind => $value) {
                $dataSet[] = "{$bind} = :{$bind}";
            }
            $dataSet = implode(", ", $dataSet);

            //transformando os parametros em array
            parse_str($params, $params);


            $stmt = Connect::getInstance()->prepare("UPDATE {$entity} SET {$dataSet} WHERE {$terms}");
            //passando para o execute um filtro dos dados mesclados para atendender o prepare
            $stmt->execute($this->filter(array_merge($data, $params)));

            //retornamos a quantidade de linhas alteradas,
            //e mesmo que não altere nenhuma linha mas o comando execute sem erros retorna 1
            return ($stmt->rowCount() ?? 1);

        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }

    }

    /**
     * @param string $entity
     * @param string $terms
     * @param string $params
     * @return Int|null
     */
    protected function delete(string $entity, string $terms, string $params): ?int
    {
        try {
            $stmt = Connect::getInstance()->prepare("DELETE FROM {$entity} WHERE {$terms}");

            //transformando os parametros em array
            parse_str($params, $params);

            $stmt->execute($params);

            //retornamos a quantidade de linhas alteradas,
            //e mesmo que não altere nenhuma linha mas o comando execute sem erros retorna 1
            return ($stmt->rowCount() ?? 1);

        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }


    }

    /**
     * @return array|null
     */
    protected function safe(): ?array
    {
        //o safe esta receber um array, por isso convertemos os dados
        //em um array
        $safe = (array)$this->data;

        //pegando os itens que não podem ser editados da classe User
        foreach (static::$safe as $unset) {
            unset($safe[$unset]);
        }
        //retorna safe com os dados correto
        return $safe;
    }

    /**
     * @param array $data
     * @return array|null
     */
    private function filter(array $data): ?array
    {
        $filter = [];

        //validando os dados recebidos por parametro, para serem salvos corretamente
        foreach ($data as $key => $value) {

            //VERIFICA SE VALUE E NULL SE FOR REPASSA O NULL CASO NÃO SEJA PASSA O VALUE COM FILTRO DE CARACTERES
            $filter[$key] = (is_null($value) ? null : filter_var($value, 515));

        }

        return $filter;
    }

    /**RESPONSAVEL POR VERIFICAR OS ITENS REQUERIDO PELO DB
     * @return bool
     */
    protected function required(): bool
    {
        //transforma em array os dados vindo do objeto data
        $data = (array)$this->data();

        //faz um loop nos items requeridos pelo DB em minha classe
        foreach (static::$required as $field) {
            //se data[$field] estiver vazio retorna falso caso não retorna true
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

}