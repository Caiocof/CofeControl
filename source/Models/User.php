<?php


namespace Source\Models;

use Source\Core\Model;

class User extends Model
{
    /**@var array $safe no update or creat */
    protected static $safe = ["id", "created_at", "updated_at"];

    /**@var static $entity database table */
    protected static $entity = "users";

    /** @var array $required table fields */
    protected static $required = ["first_name", "last_name", "email", "password"];

    //constroe os dados nescessarios para cadastrar um novo usuario
    public function bootstrap(string $firstName, string $lastName, string $email, string $password, string $document = null): ?User
    {
        $this->first_name = $firstName;
        $this->last_name = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->document = $document;

        //quando retornamos o this garantimos que o registro continue ativo para manipularmos
        return $this;
    }

    /**METODOS PARA RETORNAR TODOS OS USUARIOS
     * @param string $terms
     * @param string $params
     * @param string $columns
     * @return User|null
     */
    public function find(string $terms, string $params, string $columns = "*"): ?User
    {
        $find = $this->read("SELECT {$columns} FROM " . self::$entity . " WHERE {$terms}", $params);

        //verifica se deu alguma falha ou load não teve resultado pelo id
        if ($this->fail() || !$find->rowCount()) {
            return null;
        }

        //se der tudo certo
        return $find->fetchObject(__CLASS__);
    }

    /**METODO PARA RETORNAR UM USUARIO POR ID
     * @param int $id
     * @param string $columns
     * @return User|null
     */
    public function findById(int $id, string $columns = "*"): ?User
    {
        return $this->find("id = :id", "id={$id}", $columns);
    }


    /**METODO PARA RETORNAR UM USUARIO POR EMAIL
     * @param string $email
     * @param string $columns
     * @return User|null
     */
    public function findByEmail(string $email, string $columns = "*"): ?User
    {
        return $this->find("email = :email", "email={$email}", $columns);

    }

    /**METODO PARA BUSCAR TODOS OS USUARIO
     * @param int $limit
     * @param int $offset
     * @param string $columns
     * @return array|null
     */
    public function all(int $limit = 30, int $offset = 0, string $columns = "*"): ?array
    {
        $all = $this->read("SELECT {$columns} FROM " . self::$entity . " LIMIT :limit OFFSET :offset", "limit={$limit}&offset={$offset}");

        //verifica se deu alguma falha ou all não teve resultado pelo id
        if ($this->fail() || !$all->rowCount()) {
            return null;
        }

        //se der tudo certo
        return $all->fetchAll(\PDO::FETCH_CLASS, __CLASS__);
    }


    /**METODO PARA SALVAR E ATUALIZAR DADOS NO DB
     * @return $this|null
     */
    public function save(): ?User
    {
        //verificando os dados obrigatorios
        if (!$this->required()) {
            $this->message->warning("Nome, Sobrenome, email e senha são obrigatórios");
            return null;
        }

        //verificando o email
        if (!is_email($this->email)) {
            $this->message->warning("O email informação não é valido");
            return null;
        }

        //verificando a senha
        if (!is_passwd($this->password)) {
            $max = CONF_PASSWD_MAX_LEN;
            $min = CONF_PASSWD_MIN_LEN;

            $this->message->warning("a senha precisa ter entre {$min} e {$max} cracteres");
            return null;
        } else {
            //se a senha for valida já atribui uma hash
            $this->password = passwd($this->password);

        }

        /**User Update*/
        if (!empty($this->id)) {
            $userId = $this->id;

            //verifica se tivemos algum resultado com o email passado
            if ($this->find("email = :email AND id != :id", "email={$this->email}&id={$userId}")) {
                $this->message->warning("O e-mail informado já esta cadastrado");
                return null;
            }

            //realisando o update, passando (classe, dados, termos, parametros)
            $this->update(self::$entity, $this->safe(), "id= :id", "id={$userId}");

            //verifica se tivemos algum erro
            if ($this->fail()) {
                $this->message->error("Error ao atualizar, verifique os dados");
                return null;
            }
        }

        /**User Create*/
        if (empty($this->id)) {

            //antes de criar verificamos se o email informado ja esta cadastrado
            if ($this->findByEmail($this->email)) {
                $this->message->warning("O e-mail informado já esta cadastrado");
                return null;
            }

            $userId = $this->create(self::$entity, $this->safe());

            //verifica se tivemos algum erro
            if ($this->fail()) {
                $this->message->error("Error ao cadastrar, verifique os dados");
                return null;
            }
        }


        //depois de criar ou atualizar o usuario realimentamos data com um objeto stdClass
        $this->data = ($this->findById($userId))->data();
        return $this;

    }

    /**METODO PARA DELETRAR DADOS NO DB
     * @return $this|null
     */
    public function destroy(): ?User
    {
        //verificando se tem algum id
        if (!empty($this->id)) {
            $this->delete(self::$entity, "id = :id", "id={$this->id}");
        }

        //verifica se ocorreu algum erro
        if ($this->fail()) {
            $this->message->warning("Não foi possivel remover o usuário");
            return null;
        }

        $this->message->success("Usuário removido com sucesso");

        //depois de deletar liberamos os dados
        $this->data = null;

        //agora retornamos o proprio objeto
        return $this;
    }


}