<?php


namespace Source\Models;


use Source\Core\Model;
use Source\Core\Session;
use Source\Core\View;
use Source\Support\Email;

/**
 * Class Auth
 * @package Source\Models
 */
class Auth extends Model
{
    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("users", ["id"], ["email", "password"]);
    }


    /**METODO QUE VERIFICA SE O USUÁRIO ESTA AUTENTICADO PARA NAVEGAR
     * @return User|null
     */
    public static function user(): ?User
    {
        $session = new Session();
        if (!$session->has("authUser")) {
            return null;
        }

        return (new User())->findById($session->authUser);

    }


    /**METODO PARA DESLOGAR O USUÁRIO
     *
     */
    public static function logout(): void
    {
        $session = new Session();
        $session->unset("authUser");
    }


    /**METODO QUE REGISTRA USUARIOS
     * @param User $user
     * @return bool
     */
    public function register(User $user): bool
    {
        //verifica se teve error ao salvar
        if (!$user->save()) {
            $this->message = $user->message;
            return false;
        }

        $view = new View(__DIR__ . "/../../shared/views/email");
        $message = $view->render("confirm", [
            "first_name" => $user->first_name,
            "confirm_link" => url("/obrigado/" . base64_encode($user->email))
        ]);

        (new Email())->bootstrap(
            "Ative sua conta no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;

    }


    /**METODO RESPONSALVE PELO LOGIN DE UM USUÁRIO
     * @param string $email
     * @param string $password
     * @param bool $save
     * @return bool
     */
    public function login(string $email, string $password, bool $save = false): bool
    {

        //verifica se email é válido
        if (!is_email($email)) {
            $this->message->warning("O e-mail informado não é válido");
            return false;
        }

        if ($save) {
            //salvando o email no cookie authEmail por um periodo de 7 dias, dando acesso a todo o site
            setcookie("authEmail", $email, time() + 604800, "/");
        } else {
            //caso o campo não sejá marcado zeramos o cookie
            setcookie("authEmail", null, time() - 3600, "/");
        }

        //verifica se senha cumpri os parametros
        if (!is_passwd($password)) {
            $this->message->warning("A senha informada não é válida");
            return false;
        }

        //busca o usuario pelo email
        $user = (new User())->findByEmail($email);

        //caso não encontre
        if (!$user) {
            $this->message->error("O e-mail informado não está cadastrado");
            return false;
        }

        //verificando se a senha esta correta
        if (!passwd_verify($password, $user->password)) {
            $this->message->error("A senha informada está errada");
            return false;
        }

        //verificando se a hash da senha precisa atualizar
        if (passwd_rehash($user->password)) {
            //caso precise atualizar, atualizamos e savamos
            $user->password = $password;
            $user->save();
        }

        //LOGIN
        (new Session())->set("authUser", $user->id);
        $this->message->success("Login efetuado com sucesso")->flash();
        return true;

    }


    /**METODO RESPONSAVEL PELA RECUPERAÇÃO DE SENHA
     * @param string $email
     * @return bool
     */
    public function forget(string $email): bool
    {
        //buscando o usuário pelo e-mail informado
        $user = (new User())->findByEmail($email);

        //verificado se o email é valido
        if (!$user) {
            $this->message->error("Usuário não encontrado, favor verificar o e-mail");
            return false;
        }

        //criando um codigo aleatorio e salvando no forget da DB
        $user->forget = md5(uniqid(rand(), true));
        //persistindo os dados da tabela
        $user->save();

        //carregando o templado de email
        $view = new View(__DIR__ . "/../../shared/views/email");

        //criando a mensagem a ser enviada
        $message = $view->render("forget", [
            "first_name" => $user->first_name,
            "forget_link" => url("/recuperar/{$user->email}|{$user->forget}")
        ]);

        (new Email())->bootstrap(
            "Recupere sua senha no " . CONF_SITE_NAME,
            $message,
            $user->email,
            "{$user->first_name} {$user->last_name}"
        )->send();

        return true;

    }


    /**METODO RESPONSAVEL POR RESETAR A SENHA
     * @param string $email
     * @param string $code
     * @param string $password
     * @param string $passwordRe
     * @return bool
     */
    public function reset(string $email, string $code, string $password, string $passwordRe): bool
    {
        //busca o usuario pelo email
        $user = (new User())->findByEmail($email);

        //verifica se a conta informada existe
        if (!$user) {
            $this->message->warning("A conta para recuperação não foi encontrada");
            return false;
        }

        //verifica se o codigo esta correto
        if ($user->forget != $code) {
            $this->message->error("Desculpe, mas o código de verificação não é válido");
            return false;
        }

        if (!is_passwd($password)) {
            $min = CONF_PASSWD_MIN_LEN;
            $max = CONF_PASSWD_MAX_LEN;
            $this->message->info("Sua senha deve ter entre {$min} e {$max} caracteres");
            return false;
        }

        if ($password != $passwordRe) {
            $this->message->warning("As senhas informadas não conferem");
            return false;
        }

        $user->password = $password;
        $user->forget = null;
        $user->save();

        return true;

    }

}