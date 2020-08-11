<?php


namespace Source\Support;



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
     * @param string $message
     * @param string $toEmail
     * @param string $toName
     * @return $this
     */
    public function bootstrap(string $subject, string $message, string $toEmail, string $toName): Email
    {
        $this->data = new \stdClass();
        $this->data->subject = $subject;
        $this->data->message = $message;
        $this->data->toEmail = $toEmail;
        $this->data->toName = $toName;

        return $this;
    }

    public function attach(string $filePah, string $fileName): Email
    {
        $this->data->attach[$filePah] = $fileName;

        return $this;
    }


    /**RESPONSAVEL PELO ENVIO DO EMAIL E VALIDAÇÃO DOS DADOS RECEBIDOS
     * @param mixed|string $fromEmail
     * @param mixed|string $fromName
     * @return bool
     */
    public function send(string $fromEmail = CONF_MAIL_SENDER['address'], string $fromName = CONF_MAIL_SENDER['name']): bool
    {
        //VERIFICA SE TEM TODOS OS DADOS
        if (empty($this->data)) {
            $this->message->error("Errro ao enviar, favor verifique os dados");
            return false;
        }

        //VERIFICA SE O EMAIL DE DESTINO ESTA CORRETO
        if (!is_email($this->data->toEmail)) {
            $this->message->warning("O email de destinatário não é valido");
            return false;
        }

        //VERIFICA SE O EMAIL DE ENVIO ESTA CORRETO
        if (!is_email($fromEmail)) {
            $this->message->warning("O email de remetente não é valido");
            return false;
        }

        //PASSA TODOS OS PARAMETROS PARA O COMPONENTE MAILER
        try {
            $this->mail->Subject = $this->data->subject;
            $this->mail->msgHTML($this->data->message);
            $this->mail->addAddress($this->data->toEmail, $this->data->toName);
            $this->mail->setFrom($fromEmail, $fromName);

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