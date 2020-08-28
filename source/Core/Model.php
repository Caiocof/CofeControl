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

    /** @var string */
    protected $query;

    /** @var string */
    protected $params;

    /** @var string */
    protected $order;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /** @var string $entity database table */
    protected static $entity;

    /** @var array $protected no update or create */
    protected static $protected;

    /** @var array $entity database table */
    protected static $required;

    /**AO CONSTRUIR UM OBJETO QUE EXTEND MODEL AUTOMATICAMENTE O MESSAGE E INSTANCIADO
     * Model constructor.
     * @param string $entity database table name
     * @param array $protected table protected columns
     * @param array $required table required columns
     */
    public function __construct(string $entity, array $protected, array $required)
    {
        self::$entity = $entity;
        self::$protected = array_merge($protected, ['created_at', "updated_at"]);
        self::$required = $required;

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

    /**METODO QUE CONTROLA AS FALHAS
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


    /**METORO RESPONSAVEL PARA MONTAR A CONSULTA
     * @param string|null $terms
     * @param string|null $params
     * @param string $columns
     * @return Model|mixed
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*")
    {
        //verificando se foi passado algum termo
        if ($terms) {
            $this->query = "SELECT {$columns} FROM " . static::$entity . " WHERE {$terms}";
            //transformo meus parametros em uma string e repasso para o objeto
            parse_str($params, $this->params);
            return $this;

        }

        //caso não seja passado termos monto a query sem eles
        $this->query = "SELECT {$columns} FROM " . static::$entity;
        return $this;


    }

    /**
     * @param int $id
     * @param string $columns
     * @return Model|null|mixed
     */
    public function findById(int $id, string $columns = "*"): ?Model
    {
        $find = $this->find("id = :id", "id={$id}", $columns);
        return $find->fetch();

    }


    /**METODO PARA ORDENAR
     * @param string $columnOrder
     * @return Model
     */
    public function order(string $columnOrder): Model
    {
        $this->order = " ORDER BY {$columnOrder}";
        return $this;
    }


    /**METODO PARA LIMITAR RESULTADOS
     * @param string $limit
     * @return Model
     */
    public function limit(string $limit): Model
    {
        $this->limit = " LIMIT {$limit}";
        return $this;
    }


    /**METODO QUE DIZ APARTIR DE QUAL NUMERO VEM O RESULTADO
     * @param string $offset
     * @return Model
     */
    public function offset(string $offset): Model
    {
        $this->offset = " OFFSET {$offset}";
        return $this;
    }


    /**METODO RESPONSALVE POR EXECUTAR A CONSULTA
     * @param bool $all
     * @return null|array|mixed|Model
     */
    public function fetch(bool $all = false)
    {
        try {
            //montando a query para ser enviada
            $stmt = Connect::getInstance()->prepare($this->query . $this->order . $this->limit . $this->offset);
            //depois da query pronta executamos ela passando os parametros
            $stmt->execute($this->params);

            //verificado se não teve resultado
            if (!$stmt->rowCount()) {
                return null;
            }

            //verificando se tipo de feach a ser executado
            if ($all) {
                //retorna um fetchAll com o objeto da classe para ser manipulado, passa a responsabilidade para a filha
                return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            }

            //retorna a propria classe filha para manipulação
            return $stmt->fetchObject(static::class);

        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return null;
        }
    }


    /**METODO RESPONSAVEL POR CONTAR A QUANTIDADE DE REGISTROS
     * @param string $key
     * @return int
     */
    public function count(string $key = "id"): int
    {
        //rodo a consulta
        $stmt = Connect::getInstance()->prepare($this->query);
        //executo ela passando os parametros
        $stmt->execute($this->params);

        //retorno a quantidade de linhas resultante
        return $stmt->rowCount();
    }


    /**
     * @param array $data
     * @return int|null
     */
    protected function create(array $data): ?int
    {
        try {
            //contrução das colunas para o DB
            $columns = implode(", ", array_keys($data));

            //construção dos dados para o DB
            //colocamos os : no inico e o separador vai ter ', :' para ter como referencia
            $values = ":" . implode(", :", array_keys($data));

            //preparando a query
            $stmt = Connect::getInstance()->prepare("INSERT INTO " . static::$entity . " ({$columns}) VALUES ({$values})");
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
     * @param array $data
     * @param string $terms
     * @param string $params
     * @return int|null
     */
    protected function update(array $data, string $terms, string $params): ?int
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


            $stmt = Connect::getInstance()->prepare("UPDATE " . static::$entity . " SET {$dataSet} WHERE {$terms}");
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
     * @param string $key
     * @param string $value
     * @return bool
     */
    protected function delete(string $key, string $value): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare("DELETE FROM " . static::$entity . " WHERE {$key} = :key");
            $stmt->bindValue("key", $value, \PDO::PARAM_STR);
            $stmt->execute();

            return true;

        } catch (\PDOException $exception) {
            $this->fail = $exception;
            return false;
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
        foreach (static::$protected as $unset) {
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

            //VERIFICA SE VALUE E NULL SE FOR REPASSA O NULL CASO NÃO SEJA PASSA O VALUE COM FILTRO
            $filter[$key] = (is_null($value) ? null : filter_var($value, FILTER_DEFAULT));

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