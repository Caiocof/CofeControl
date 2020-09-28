<?php


namespace Source\App;


use Source\Core\Controller;
use Source\Core\View;
use Source\Models\Auth;
use Source\Models\CoffeeApp\AppCategory;
use Source\Models\CoffeeApp\AppInvoice;
use Source\Models\Post;
use Source\Models\Report\Access;
use Source\Models\Report\Online;
use Source\Models\User;
use Source\Support\Email;
use Source\Support\Message;

class App extends Controller
{
    /** @var User */
    private $user;

    public function __construct()
    {
        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_APP . "/");

        //RESTRIÇÕES DE ACESSO
        if (!$this->user = Auth::user()) {
            $this->message->warning("Efetue login para acessar está pagina.")->flash();
            redirect("/entrar");
        }

        (new Access())->report();
        (new Online())->report();

        (new AppInvoice())->fixed($this->user, 3);

    }

    /**
     * APP HOME
     */
    public function home()
    {
        $head = $this->seo->render(
            "Olá {$this->user->first_name}. Vamos controlar? - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        //CHART

        $dateChart = [];
        //puxando registros de 5 meses anteriores
        for ($month = -4; $month <= 0; $month++) {
            //atibui cada mes a variavel de strat do grafico
            $dateChart[] = date("m/Y", strtotime("{$month}month"));
        }

        //criação do objeto a ser mandado para o template
        $chartData = new \stdClass();
        //separando as datas por virgula para enviar
        $chartData->categories = "'" . implode("','", $dateChart) . "'";
        $chartData->expense = "0,0,0,0,0";
        $chartData->income = "0,0,0,0,0";

        //buscando os dados reais no banco com os dados do usuario
        $chart = (new AppInvoice())
            ->find("user_id = :user AND status = :status AND due_at >= DATE(now() - INTERVAL 4 MONTH) 
                            GROUP BY year(due_at) ASC, month(due_at) ASC",
                "user={$this->user->id}&status=paid",
                "
                year(due_at) AS due_year,
                month(due_at) AS due_month,
                DATE_FORMAT(due_at, '%m/%Y') AS due_date,
                (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income' AND  year(due_at) = due_year AND month(due_at) = due_month) AS income,               
                (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense' AND  year(due_at) = due_year AND month(due_at) = due_month) AS expense                
                "
            )
            ->limit(5)
            ->fetch(true);

        //pego o resultado da consulta acima verifica se retornou true e alimenta os campos
        if ($chart) {
            $chartCategories = [];
            $chartExpense = [];
            $chartIncome = [];

            //faz um loop com os dados recebidos para criar os indices
            foreach ($chart as $chartItem) {
                $chartCategories[] = $chartItem->due_date;
                $chartExpense[] = $chartItem->expense;
                $chartIncome[] = $chartItem->income;
            }

            //repera os resultados da data por virgura
            $chartData->categories = "'" . implode("','", $chartCategories) . "'";
            //pega os valores e passa por um map para transformar os numeros em positivos inteiros "abs"
            $chartData->expense = implode(",", array_map("abs", $chartExpense));
            $chartData->income = implode(",", array_map("abs", $chartIncome));
        }
        //END CHART


        //INCOME && EXPENSE
        $income = (new AppInvoice())
            //busca todos as contas não recebidas no periodo do dia atual mais 1 mes
            ->find("user_id = :user AND type = 'income' AND status = 'unpaid' AND date(due_at) <= date(now() + INTERVAL 1 MONTH)", "user={$this->user->id}")
            ->order("due_at")
            ->fetch(true);


        $expense = (new AppInvoice())
            //busca todos as contas não pagas no periodo do dia atual mais 1 mes
            ->find("user_id = :user AND type = 'expense' AND status = 'unpaid' AND date(due_at) <= date(now() + INTERVAL 1 MONTH)", "user={$this->user->id}")
            ->order("due_at")
            ->fetch(true);
        //END INCOME && EXPENSE


        //WALLET
        $wallet = (new AppInvoice())
            //busca a carteira e cria duas sub querys para pegar os debitos e receitas
            ->find("user_id = :user AND status = :status",
                "user={$this->user->id}&status=paid",
                "
                    (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income') AS income,                
                    (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense') AS expense")
            ->fetch();

        //verifica os dados recebidos e cria a wallet de fato para se repasada ao layout
        if ($wallet) {
            $wallet->wallet = $wallet->income - $wallet->expense;
        }
        //END WALLET


        //POSTS
        $posts = (new Post())->find()->limit(3)->order("post_at DESC")->fetch(true);
        //END POSTS


        echo $this->view->render("home", [
            "head" => $head,
            "chart" => $chartData,
            "income" => $income,
            "expense" => $expense,
            "wallet" => $wallet,
            "posts" => $posts

        ]);
    }


    public function filter(array $data): void
    {

    }

    /**APP INCOME (Receber)
     * @param array|null $data
     */
    public function income(?array $data): void
    {
        $head = $this->seo->render(
            "Minhas receitas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $categories = (new AppCategory())
            ->find("type = :t", "t=income", "id,name")
            ->order("order_by, name")
            ->fetch(true);


        echo $this->view->render("invoices", [
            "user" => $this->user,
            "head" => $head,
            "type" => "income",
            "categories" => $categories,
            "invoices" => (new AppInvoice())->filter($this->user, "income", ($data ?? null)),
            //criando os filtros que seram passador pela URL
            "filter" => (object)[
                "status" => ($data['status'] ?? null),
                "category" => ($data['category'] ?? null),
                //vamos substituir  - por / na data se ela for diferente de vazia
                "date" => (!empty($data['date']) ? str_replace("-", "/", $data['date']) : null)
            ]
        ]);
    }


    /**APP EXPENSE (Pagar)
     * @param array|null $data
     */
    public function expense(?array $data): void
    {
        $head = $this->seo->render(
            "Minhas despesas - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        $categories = (new AppCategory())
            ->find("type = :t", "t=expanse", "id,name")
            ->order("order_by, name")
            ->fetch(true);

        echo $this->view->render("invoices", [
            "user" => $this->user,
            "head" => $head,
            "type" => "expanse",
            "categories" => $categories,
            "invoices" => (new AppInvoice())->filter($this->user, "expanse", ($data ?? null)),
            //criando os filtros que seram passador pela URL
            "filter" => (object)[
                "status" => ($data['status'] ?? null),
                "category" => ($data['category'] ?? null),
                //vamos substituir  - por / na data se ela for diferente de vazia
                "date" => (!empty($data['date']) ? str_replace("-", "/", $data['date']) : null)
            ]
        ]);
    }


    /**
     * @param array $data
     */
    public
    function launch(array $data): void
    {
        //criando o limite de 20 lançamentos a cada 5 minutos
        if (request_limit("applaunch", 20, 60 * 5)) {
            $json['message'] = $this->message->warning("Foi muito rápido {$this->user->first_name}! Por favor aguarde 5 minutos para novos lançamentos.")->render();
            echo json_encode($json);
            return;
        }

        //verificando se a quantidade de parcela esta entre 2 e 360
        if (!empty($data['enrollments']) && ($data['enrollments'] < 2 || $data['enrollments'] > 360)) {
            $json['message'] = $this->message->warning("Ooops {$this->user->first_name}! Para lançar o número de parcelas deve ser entre 2 e 360 vezes")->render();
            echo json_encode($json);
            return;
        }

        //removendo qualquer entrada de script
        $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        //verifica a data, já passou entra como paga se não entra como a pagar
        $status = (date($data['due_at']) <= date("Y-m-d") ? "paid" : "unpaid");


        //pegando todos os dados pra envior ao DB
        $invoice = (new AppInvoice());
        $invoice->user_id = $this->user->id;
        $invoice->wallet_id = $data['wallet'];
        $invoice->category_id = $data['category'];
        $invoice->invoice_of = null;
        $invoice->description = $data['description'];
        $invoice->type = ($data['repeat_when'] == "fixed" ? "fixed_{$data['type']}" : $data['type']);
        $invoice->value = str_replace([".", ","], ["", "."], $data['value']);
        $invoice->currency = $data['currency'];
        $invoice->due_at = $data['due_at'];
        $invoice->repeat_when = $data['repeat_when'];
        $invoice->period = ($data['period'] ?? "month");
        $invoice->enrollments = ($data['enrollments'] ?? 1);
        $invoice->enrollment_of = 1;
        $invoice->status = ($data["repeat_when"] == "fixed" ? "paid" : $status);


        //verificando se houve o salvamento
        if (!$invoice->save()) {
            $json['message'] = $invoice->message()->render();
            echo json_encode($json);
            return;
        }


        //verificando se é uma conta parcelado, caso seja cria as demais parcelas
        if ($invoice->repeat_when == "enrollment") {
            $invoiceOf = $invoice->id;

            for ($enrollment = 1; $enrollment < $invoice->enrollments; $enrollment++) {
                $invoice->id = null;
                $invoice->invoice_of = $invoiceOf;
                $invoice->due_at = date("Y-m-d", strtotime($data['due_at'] . "+{$enrollment}month"));
                $invoice->status = (date($invoice->due_at) <= date("Y-m-d") ? "paid" : "unpaid");
                $invoice->enrollment_of = $enrollment + 1;
                $invoice->save();
            }
        }

        if ($invoice->type == "income") {
            $this->message->success("Receita lançada com sucesso. Use o filtro para controlar!")->render();
        } else {
            $this->message->success("Despesa lançada com sucesso. Use o filtro para controlar!")->render();
        }

        $json['reload'] = true;
        echo json_encode($json);
        return;
    }


    /**
     * @param array $data
     * @throws \Exception
     */
    public
    function support(array $data): void
    {

        //verificando se o campo da mensagem não esta vazio
        if (empty($data['message'])) {
            $json['message'] = $this->message->warning("Por favor {$this->user->first_name}, escreve a sua mensagem!")->render();
            echo json_encode($json);
            return;
        }


        //limitando a quantidade de envios a 3 a cada 5 minutos
        if (request_limit("appsupport", 3, 60 * 5)) {
            $json['message'] = $this->message->warning("Por favor {$this->user->first_name}, aguarde 5 minutos para enviar novos contatos!")->render();
            echo json_encode($json);
            return;
        }

        //verificando se o usuario não esta enviando a mesma mensagem mais de uma vez
        if (request_repeat("message", $data['message'])) {
            $json['message'] = $this->message->info("Olá {$this->user->first_name}, já recebemos o seu contato, em breve responderemos.")->render();
            echo json_encode($json);
            return;
        }

        //criando um assunto com a data atual + tema escolhido pelo usuario
        $subject = date_fmt() . " - {$data['subject']}";
        //filtando a mensagem
        $message = filter_var($data['message'], FILTER_SANITIZE_STRING);

        //buscando a view do email
        $view = new View(__DIR__ . "/../../shared/views/email");
        //criando o corpo do email
        $body = $view->render("email", [
            "subject" => $subject,
            "message" => str_textarea($message)
        ]);

        $email = new Email();

        //criando o email e enviando
        $email->bootstrap(
            $subject,
            $body,
            CONF_MAIL_SUPPORT,
            "Support " . CONF_SITE_NAME
        )->queue($this->user->email, "{$this->user->first_name} {$this->user->last_name}");


        $this->message->success("Recebemos sua solicitação {$this->user->first_name}. Agradecemos pelo contato e responderemos em preve")->flash();
        //recarregando a pagina depois do envio
        $json['reload'] = true;
        echo json_encode($json);
    }


    /**
     * APP INVOICE (Fatura)
     */
    public
    function invoice()
    {
        $head = $this->seo->render(
            "Aluguel - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("invoice", [
            "head" => $head
        ]);
    }

    /**
     * APP PROFILE (Perfil)
     */
    public
    function profile()
    {
        $head = $this->seo->render(
            "Meu perfil - " . CONF_SITE_NAME,
            CONF_SITE_DESC,
            url(),
            theme("/assets/images/share.jpg"),
            false
        );

        echo $this->view->render("profile", [
            "head" => $head
        ]);
    }

    /**
     * APP LOGOUT
     */
    public
    function logout()
    {
        (new Message())->info("Você saiu com sucesso " . Auth::user()->first_name . ". Volte logo :)")->flash();

        Auth::logout();
        redirect("/entrar");
    }
}