<?php


namespace Source\App;


use http\Url;
use Source\Core\Controller;
use Source\Models\Auth;
use Source\Models\Category;
use Source\Models\Faq\Question;
use Source\Models\Post;
use Source\Models\User;
use Source\Support\Pager;
use function League\Plates\Util\id;

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
        $head = $this->seo->render(
            CONF_SITE_NAME . " - " . CONF_SITE_TITLE,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("home", [
            "head" => $head,
            "video" => "8nvXVLu3Lxc",
            "blog" => (new Post())
                ->find()
                ->order("post_at DESC")
                ->limit(6)->fetch(true)
        ]);
    }


    /**
     * SITE ABOUT
     */
    public function about(): void
    {
        $head = $this->seo->render(
            "Descubra o " . CONF_SITE_NAME . " - " . CONF_SITE_DESC,
            CONF_SITE_DESC,
            url("/sobre"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("about", [
            "head" => $head,
            "video" => "H43fXodv6WY",
            "faq" => (new Question())
                ->find("channel_id = :id", "id=1", "question, response")
                ->order("order_by")
                ->fetch(true)
        ]);
    }


    /**SITE BLOG
     * @param array|null $data
     */
    public function blog(?array $data): void
    {
        $head = $this->seo->render(
            "Blog -  " . CONF_SITE_NAME,
            "Confira em nosso blog as dicas de como controlar melhor suas contas!",
            url("/blog"),
            theme("/assets/images/share.jpg")
        );


        $blog = (new Post())->find();
        $pager = new Pager(url("/blog/pg/"));

        // lista no maximo 100 resultado dividindo de 10 por pagina, pegando a pagina no data, se não tiver retorna 1
        $pager->pager($blog->count(), 9, ($data['page'] ?? 1));

        echo $this->view->render("blog", [
            "head" => $head,
            "blog" => $blog->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);
    }


    /**SITE BLOG SEARCH
     * @param array $data
     */
    public function blogSearch(array $data): void
    {
        if (!empty($data['s'])) {
            $search = filter_var($data['s'], FILTER_SANITIZE_STRIPPED);
            echo json_encode(["redirect" => url("/blog/buscar/{$search}/1")]);
            return;
        }

        if (empty($data['terms'])) {
            redirect("/blog");
        }

        $search = filter_var($data['terms'], FILTER_SANITIZE_STRIPPED);
        $page = (filter_var($data['page'], FILTER_VALIDATE_INT) >= 1 ? $data['page'] : 1);

        $head = $this->seo->render(
            "Pesquisa por {$search} - " . CONF_SITE_NAME,
            "Confira os resultados de sua pesquisa para {$search}",
            url("/blog/buscar/{$search}/{$page}"),
            theme("/assets/images/share.jpg")
        );

        $blogSearch = (new Post())->find("title LIKE :s OR subtitle LIKE :s", "s=%{$search}%");

        if (!$blogSearch->count()) {
            echo $this->view->render("blog", [
                "head" => $head,
                "title" => "PESQUISA POR:",
                "search" => $search
            ]);
            return;
        }

        $pager = new Pager(url("/blog/buscar/{$search}/"));
        $pager->pager($blogSearch->count(), 9, $page);

        echo $this->view->render("blog", [
            "head" => $head,
            "title" => "PESQUISA POR:",
            "search" => $search,
            "blog" => $blogSearch->limit($pager->limit())->offset($pager->offset())->fetch(true),
            "paginator" => $pager->render()
        ]);

    }


    /**SITE BLOG POST
     * @param array $data
     */
    public function blogPost(array $data): void
    {

        $post = (new Post())->findByUri($data['uri']);

        if (!$post) {
            redirect("/404");
        }
        $post->views += 1;
        $post->save();

        $head = $this->seo->render(
            "{$post->title} - " . CONF_SITE_NAME,
            $post->subtitle,
            url("/blog/{$post->uri}"),
            image($post->cover, 1200, 628)
        );

        echo $this->view->render("blog-post", [
            "head" => $head,
            "post" => $post,
            "related" => (new Post())
                ->find("category = :c AND id != :i", "c={$post->category}&i={$post->id}")
                ->order("rand()")
                ->limit(3)
                ->fetch(true)
        ]);
    }


    /**
     *SITE LOGIN
     */
    public function login(): void
    {
        $head = $this->seo->render(
            "Entrar - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/entrar"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("auth-login", [
            "head" => $head
        ]);
    }


    /**
     *SITE FORGET PASSWORD
     */
    public function forget(): void
    {
        $head = $this->seo->render(
            "Recuperar Senha - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/recuperar"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("auth-forget", [
            "head" => $head
        ]);
    }


    /**SITE REGISTER ACCOUNT
     * @param array|null $data
     */
    public function register(?array $data): void
    {

        //verifica se o campo csrf tem conteudo
        if (!empty($data['csrf'])) {

            //verifica se o token csrf esta valido
            if (!csrf_verify($data)) {
                $json['message'] = $this->message->error("Erro ao enviar, favor use o formulário")->render();
                echo json_encode($json);
                return;
            }

            //verifica se todos os campos estão preenchidos
            if (in_array("", $data)) {
                $json['message'] = $this->message->info("Preencha todos os campos para criar sua conta")->render();
                echo json_encode($json);
                return;
            }

            $auth = new Auth();
            $user = new User();

            $user->bootstrap(
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['password']
            );

            if ($auth->register($user)) {
                $json['message'] = url("/confirma");
            } else {
                $json['message'] = $auth->message()->render();
            }

            echo json_encode($json);
            return;


        }


        $head = $this->seo->render(
            "Cadastrar - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/cadastrar"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("auth-register", [
            "head" => $head
        ]);
    }


    /**
     * SITE OPTIN CONFIRM
     */
    public function confirm(): void
    {
        $head = $this->seo->render(
            "Confirme Seu Cadastro - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/confirma"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("optin-confirm", [
            "head" => $head
        ]);
    }


    /**
     * SITE OPTIN SUCCESS
     */
    public function success(): void
    {
        $head = $this->seo->render(
            "Bem-vindo(a) ao " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url("/obrigado"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("optin-success", [
            "head" => $head
        ]);
    }


    /**
     * SITE TERMS
     */
    public function terms(): void
    {
        $head = $this->seo->render(
            CONF_SITE_NAME . " - Termos de uso",
            CONF_SITE_DESC,
            url("/termos"),
            theme("/assets/images/share.jpg")
        );
        echo $this->view->render("terms", [
            "head" => $head,

        ]);
    }

    /*
     * SITE NAV ERROR
     * @param array $data
     */
    public function error(array $data): void
    {
        $error = new \stdClass();

        switch ($data['errcode']) {
            case "problemas":
                $error->code = "Ooops";
                $error->title = "Estamos enfrentando problemas! :/";
                $error->message = "Parece que nosso serviço não está disponível no momento. Já estamos vendo isso mas caso precise, envie um e-mail :)";
                $error->linkTitle = "ENVIAR E-MAIL";
                $error->link = "mailto:" . CONF_MAIL_SUPPORT;
                break;
            case "manutencao":
                $error->code = "Ooops";
                $error->title = "Desculpe. Estamos em manuteção! :/";
                $error->message = "Voltamos logo! Por hora estamos trabalhando para melhorar nosso conteúdo para você controlar melhor suas contas :)";
                $error->linkTitle = null;
                $error->link = null;
                break;
            default:
                $error->code = $data['errcode'];
                $error->title = "Ooops. Conteúdo indisponível :/";
                $error->message = "Sentimos muito, mas o conteúdo que você tentou acessar não existe, está indisponível no momento ou foi removido :/";
                $error->linkTitle = "Contínue navegando!";
                $error->link = url_back();
                break;
        }

        $head = $this->seo->render(
            "{$error->code} | {$error->title}",
            $error->message,
            url("/ooops/{$error->code}"),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("error", [
            "head" => $head,
            "error" => $error

        ]);
    }

}