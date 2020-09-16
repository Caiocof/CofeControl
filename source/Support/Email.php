<?php


namespace Source\Support;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Source\Core\Connect;

/**
 * Class Email
 * @package Source\Core
 */
class Email
{
    /** @var array */
    private $data;

    /** @var PHPMailer */
    private $mail;

    /** @var Message */
    private $message;

    /**RESPONSAVEL PELA CONFIGURAÇÃO DO EMAIL
     * Email constructor.
     */
    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->message = new Message();

        //setup
        $this->mail->isSMTP();
        $this->mail->setLanguage(CONF_MAIL_OPTION_LANG);
        $this->mail->isHTML(CONF_MAIL_OPTION_HTML);
        $this->mail->SMTPAuth = CONF_MAIL_OPTION_AUTH;
        $this->mail->SMTPSecure = CONF_MAIL_OPTION_SECURE;
        $this->mail->CharSet = CONF_MAIL_OPTION_CHARSET;


        //auth
        $this->mail->Host = CONF_MAIL_HOST;
        $this->mail->Port = CONF_MAIL_PORT;
        $this->mail->Username = CONF_MAIL_USER;
        $this->mail->Password = CONF_MAIL_PASS;

    }

    /**RESPONSAVEL PELA CONTRUÇÃO DO EMAIL
     * @param string $subject
     * @param string $body
     * @param string $recipient
     * @param string $recipientName
     * @return $this
     */
    public function bootstrap(string $subject, string $body, string $recipient, string $recipientName): Email
    {
        $this->data = new \stdClass();
        $this->data->subject = $subject;
        $this->data->body = $body;
        $this->data->recipient_email = $recipient;
        $this->data->recipient_name = $recipientName;

        return $this;
    }


    /**
     * @param string $filePah
     * @param string $fileName
     * @return $this
     */
    public function attach(string $filePah, string $fileName): Email
    {
        $this->data->attach[$filePah] = $fileName;

        return $this;
    }


    /**RESPONSAVEL PELO ENVIO DO EMAIL E VALIDAÇÃO DOS DADOS RECEBIDOS
     * @param mixed|string $from
     * @param mixed|string $fromName
     * @return bool
     */
    public function send(string $from = CONF_MAIL_SENDER['address'], string $fromName = CONF_MAIL_SENDER['name']): bool
    {
        //VERIFICA SE TEM TODOS OS DADOS
        if (empty($this->data)) {
            $this->message->error("Errro ao enviar, favor verifique os dados");
            return false;
        }

        //VERIFICA SE O EMAIL DE DESTINO ESTA CORRETO
        if (!is_email($this->data->recipient_email)) {
            $this->message->warning("O email de destinatário não é valido");
            return false;
        }

        //VERIFICA SE O EMAIL DE ENVIO ESTA CORRETO
        if (!is_email($from)) {
            $this->message->warning("O email de remetente não é valido");
            return false;
        }

        //PASSA TODOS OS PARAMETROS PARA O COMPONENTE MAILER
        try {
            $this->mail->Subject = $this->data->subject;
            $this->mail->msgHTML($this->data->body);
            $this->mail->addAddress($this->data->recipient_email, $this->data->recipient_name);
            $this->mail->setFrom($from, $fromName);

            //VERIFICA SE EXISTE ARQUIVOS PARA SEREM ANEXADOS AO EMAIL
            if (!empty($this->data->attach)) {

                //CASO TENHA ARQUIVOS PERCORRE TODOS ELES ADICIONANDO AO CORPO DO EMAIL
                foreach ($this->data->attach as $path => $name) {
                    $this->mail->addAttachment($path, $name);

                }
            }

            $this->mail->send();
            return true;


        } catch (Exception $exception) {
            $this->message->error($exception->getMessage());
            return false;
        }

    }


    /**METODO RESPONSAVEL POR CRIAR A LISTA DE DISPAROS DE EMAIL
     * @param mixed|string $from
     * @param mixed|string $fromName
     * @return bool
     */
    public function queue(string $from = CONF_MAIL_SENDER['address'], string $fromName = CONF_MAIL_SENDER['name']): bool
    {
        try {

            $stmt = Connect::getInstance()->prepare(
                "INSERT INTO
                    mail_queue (subject, body, from_email, from_name, recipient_email, recipient_name)
                    VALUES (:subject, :body, :from_email, :from_name, :recipient_email, :recipient_name)"
            );
            $stmt->bindValue(":subject", $this->data->subject, \PDO::PARAM_STR);
            $stmt->bindValue(":body", $this->data->body, \PDO::PARAM_STR);
            $stmt->bindValue(":from_email", $from, \PDO::PARAM_STR);
            $stmt->bindValue(":from_name", $fromName, \PDO::PARAM_STR);
            $stmt->bindValue(":recipient_email", $this->data->recipient_email, \PDO::PARAM_STR);
            $stmt->bindValue(":recipient_name", $this->data->recipient_name, \PDO::PARAM_STR);

            $stmt->execute();
            return true;

        } catch (\PDOException $exception) {
            $this->message->error($exception->getMessage());
            return false;
        }
    }


    /**METODO RESPONSAVEL POR ENVIAR OS EMAILS AGENDADOS
     * @param int $perSecond
     */
    public function sendQueue(int $perSecond = 2)
    {
        //query para buscar somente os email que não foram disparados ainda
        $stmt = Connect::getInstance()->query("SELECT * FROM mail_queue WHERE sent_at IS NULL");
        //se tiver algum retorno da query
        if ($stmt->rowCount()) {
            //passa por todos os resultados
            foreach ($stmt->fetchAll() as $send) {

                //usa os dados recebidos para criar os emails para disparo
                $email = $this->bootstrap(
                    $send->subject,
                    $send->body,
                    $send->recipient_email,
                    $send->recipient_name
                );

                //verifica se tudo esta correto com o envio, passando email e nome vindos do banco
                if ($email->send($send->from_email, $send->from_name)) {
                    //validando os desparos por segundo
                    usleep(1000000 / $perSecond);
                    //atualizo a coluna sent_at no DB
                    Connect::getInstance()->exec("UPDATE mail_queue SET sent_at = NOW() WHERE id = {$send->id}");

                }
            }

        }

    }

    /**CRIA UM OBJETO DO MEU PHPMAILER
     * @return PHPMailer
     */
    public function mail(): PHPMailer
    {
        return $this->mail;
    }

    /**CRIA UM OBJETO DO MEU MESSAGE
     * @return Message
     */
    public function message(): Message
    {
        return $this->message;
    }

}