<?php


/**####################
 * ###   VALIDATE   ###
 */####################

/**VERIFICA SE É UM E-MAIL VALIDO
 * @param string $email
 * @return bool
 */
function is_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**VERIFICA SE É UM PASSWORD VALIDO
 * @param string $passwd
 * @return bool
 */
function is_passwd(string $password): bool
{
    //verifica se não e uma hash, antes de verificar os parametros definidos ou se a hack do password for maior que o minimo e menor que o maximo
    if (password_get_info($password)['algo'] || (mb_strlen($password) >= CONF_PASSWD_MIN_LEN && mb_strlen($password) <= CONF_PASSWD_MAX_LEN)) {
        return true;
    }

    return false;
}

/**TRANSFORMA A SENHA EM UMA HASH PARA ENVIAR AO BANCO
 * @param string $password
 * @return string
 */
function passwd(string $password): string
{
    if (!empty(password_get_info($password)['algo'])) {
        return $password;
    }

    return password_hash($password, CONF_PASSWD_ALGO, CONF_PASSWD_OPTION);
}

/**VERIFICA SE A SENHA CONFERE COM A HASH NO BANCO
 * @param string $password
 * @param string $hash
 * @return bool
 */
function passwd_verify(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**VERIFICA SE É PRECISO GERAR UMA NOVA HASH PARA O USUARIO
 * @param string $hash
 * @return bool
 */
function passwd_rehash(string $hash): bool
{
    return password_needs_rehash($hash, CONF_PASSWD_ALGO, CONF_PASSWD_OPTION);
}

/**###################
 * ###   REQUEST   ###
 */###################


/**CRIA UM INPUT PARA SER COLOCADO NO FORMULARIO, E MANTER O TOKEN DA SESSÃO ATIVO
 * @return string
 */
function csrf_input(): string
{
    session()->csrf();
    //vai criar um campo para o formulario e buscar o token para o valor, caso o token não exista ele deixa em branco
    return "<input type='hidden' name='csrf' value='" . (session()->csrf_token ?? "") . "'/>";
}

/**VERIFICA SE OS TOKENS SÃO IGUAIS CASO NÃO SEJA REPROVA O ENVIO
 * @param $request
 * @return bool
 */
function csrf_verify($request): bool
{
    //verifica se o token da sessao esta vazio, ou se o token da request esta e também se o dois são diferente
    if (empty(session()->csrf_token) || empty($request['csrf']) || session()->csrf_token != $request['csrf']) {
        return false;
    }
    return true;
}

/**METODO QUE VERIFICA SE EXISTE ALGUMA MENSAGEM NO FLASH
 * @return string|null
 */
function flash(): ?string
{
    $session = new \Source\Core\Session();
    if ($flash = $session->flash()) {
        echo $flash;
    }
    return null;
}


/**METODO RESPONSAVEL POR LIMITAR A QUANTIDADE DE ENVIOS
 * @param string $key
 * @param int $limit
 * @param int $seconds
 * @return bool
 */
function request_limit(string $key, int $limit = 5, int $seconds = 60): bool
{

    $session = new \Source\Core\Session();
    //verifica se já existe a sessão, depois se o tempo dela esta dentro do permitido, depois se a requisição e menor
    //que a quantidade permitida
    if ($session->has($key) && $session->$key->time >= time() && $session->$key->requests < $limit) {
        $session->set($key, [
            "time" => time() + $seconds,
            "requests" => $session->$key->requests + 1
        ]);
        return false;
    }

    //verifica se o limite foi atingido
    if ($session->has($key) && $session->$key->time >= time() && $session->$key->requests >= $limit) {
        return true;
    }

    $session->set($key, [
        "time" => time() + $seconds,
        "requests" => 1
    ]);

    return false;

}


/**METODO RESPONSAVEL POR CONTROLAR A REPETIÇÃO DE ENVIOS E EMAIL
 * @param string $field
 * @param string $value
 * @return bool
 */
function request_repeat(string $field, string $value): bool
{
    $session = new \Source\Core\Session();

    //verifica se existe o campo field, e se o campo tem o mesmo valor do argumento value
    if ($session->has($field) && $session->$field == $value) {
        return true;
    }

    $session->set($field, $value);
    return false;

}


/**##################
 * ###   STRING   ###
 */##################

/**PEGA UMS STRING QUALQUER E TRANSFORTA EM UMA URL
 * @param string $string
 * @return string
 */
function str_slug(string $string): string
{
    $string = filter_var(mb_strtolower($string), FILTER_SANITIZE_STRIPPED);
    $formats = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª';
    $replace = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                 ';

    //trim remove todos os espaços desnecessarios
    //strtr(font dos dados, oque sera substituido, pelo que vamos substituir)
    //utf8_decode() e usado para que o caracter a ser substituido não suma
    $slug = trim(strtr(utf8_decode($string), utf8_decode($formats), $replace));

    //substituindo os espaços entre palavras por traços
    $subSpace = str_replace(" ", "-", $slug);

    //transformando os traços sobrando por um unico traço
    $subSpace2 = str_replace(["-----", "----", "---", "--"], "-", $subSpace);

    return $subSpace2;
}

/**CONVERTE REQUISIÇÕES EM NOMES DE CLASSES
 * @param string $string
 * @return string
 */
function str_studly_case(string $string): string
{
    $string = str_slug($string);

    //converte os traços em espaços e deixando a primeira letra de casa palavra em Maiusculo
    $studlyCase = mb_convert_case(str_replace("-", " ", $string), MB_CASE_TITLE);

    //tira os espaços entre as palavras para forma um titulo
    $studlyCase2 = str_replace(" ", "", $studlyCase);

    return $studlyCase2;
}

/**CRIA UM FORMATO ADEQUADO PARA CRIAÇÃO DE METODOS
 * @param string $string
 * @return string
 */
function str_camel_case(string $string): string
{
    return lcfirst(str_studly_case($string));
}

/**CRIA TITULOS PARA BLOG
 * @param string $string
 * @return string
 */
function str_title(string $string): string
{

    //filtra a string para não receber script e depois coloca ela em formato de TITLE
    return mb_convert_case(filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS), MB_CASE_TITLE);
}

/**METORO QUE REMOVES OS ENTER's A MAIS NO TEXTAREA
 * @param string $text
 * @return string
 */
function str_textarea(string $text): string
{
    $text = filter_var($text, FILTER_SANITIZE_STRIPPED);
    $arrayReplace = ["&#10;", "&#10;&#10;", "&#10;&#10;&#10;", "&#10;&#10;&#10;&#10;", "&#10;&#10;&#10;&#10;&#10;", "&#10;&#10;&#10;&#10;&#10;&#10;"];
    return "<p>" . str_replace($arrayReplace, "</p><p>", $text) . "</p>";

}

/**USADO PARA CRIAR SUBTITULOS EM BLOG COM LIMITAÇÃO DE PALAVRAS
 * @param string $string
 * @return string
 */
function str_limit_words(string $string, int $limit, string $pointer = "..."): string
{
    //tira os espaços desnecessarios e filtra scripts
    $string = trim(filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS));

    //separando as palavras da string
    $arrWords = explode(" ", $string);

    //contando o numero de palavras
    $numWords = count($arrWords);

    //verifica se o numero de palavras e menor que o limite passado no metodo
    if ($numWords < $limit) {
        return $string;
    }

    //transformando o array em um texto novamente, pegando do inicio até o limite
    $words = implode(" ", array_slice($arrWords, 0, $limit));

    return "{$words}{$pointer}";
}

/**CRIANDO SUB-TITULOS COM LIMITE DE CARACTERES
 * @param string $string
 * @param int $limit
 * @param string $pointer
 * @return string
 */
function str_limit_chars(string $string, int $limit, string $pointer = "..."): string
{
    //tira os espaços desnecessarios e filtra scripts
    $string = trim(filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS));

    //verifica se o limite e de caracter informado e menor ou igual a quantidade da string
    if (mb_strlen($string) <= $limit) {
        return $string;
    }

    //pega o temalho da string cortada e o ultimo espaço da string
    $chars = mb_strrpos(mb_substr($string, 0, $limit), " ");

    //pega o texto, começa a contagem de 0 e vai ate o ultimo espaço dentro do limite informado
    $chars2 = mb_substr($string, 0, $chars);
    return "{$chars2}{$pointer}";
}

/**METODO QUE CONVERTE MOEDA
 * @param string $price
 * @return string
 */
function str_price(string $price): string
{
    return number_format($price, 2, ",", ".");
}


/**###############
 * ###   URL   ###
 */###############

/**METODO PARA NAVEGAR ENTRE URLs
 * @param string $path
 * @return string
 */
function url(string $path = null): string
{
    //verificando se na URL tem o endereço local
    if (strpos($_SERVER['HTTP_HOST'], "localhost")) {
        //verificando se tem o path
        if ($path) {
            //verifica se foi pasado uma / por parametro, caso foi remove, se não passa o path direto
            $pathF = ($path[0] == "/" ? mb_substr($path, 1) : $path);
            //retornando a url base concatenada com uma barra e o caminho do path
            return CONF_URL_TEST . "/" . $pathF;
        }
        return CONF_URL_TEST;
    }

    //ja que não estamos em URL local, vamos retornar um endereço do servidor
    if ($path) {
        //verifica se foi pasado uma / por parametro, caso foi remove, se não passa o path direto
        $pathF = ($path[0] == "/" ? mb_substr($path, 1) : $path);
        //retornando a url base concatenada com uma barra e o caminho do path
        return CONF_URL_BASE . "/" . $pathF;
    }
    return CONF_URL_BASE;

}

/**METODO PARA RETORNAR PARA A PAGINA ANTERIOR DE NAVEGAÇÃO NOS ERROS
 * @return string
 */
function url_back(): string
{
    //retorna a url anterior que estava acessando caso não tenha retorna ela mesma
    return ($_SERVER['HTTP_REFERER'] ?? url());
}


/**METODO PARA REDIRECIONAMENTO INTERNO
 * @param string $url
 */
function redirect(string $url): void
{
    header("HTTP/1.1 302 Redirect");

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        header("Location: {$url}");
        exit;
    }

    //verifica se já não estamos na URL de redirecionamento antes de acessar
    if (filter_input(INPUT_GET, "ROUTE", FILTER_DEFAULT) != $url) {
        $location = url($url);
        header("Location: {$location}");
        exit;
    }


}


/**##################
 * ###   ASSETS   ###
 */##################

/**METORO QUE RETORNA O USUARIO LOGADO
 * @return \Source\Models\User|null
 */
function user(): ?\Source\Models\User
{
    return \Source\Models\Auth::user();
}


/**METODO PARA RETORNAR OS CAMINHOS DO TEMA APLICADO NO SITE
 * @param string|null $path
 * @param string $theme
 * @return string
 */
function theme(string $path = null, string $theme = CONF_VIEW_THEME): string
{
    //verificando se na URL tem o endereço local
    if (strpos($_SERVER['HTTP_HOST'], "localhost")) {
        //verificando se tem o path
        if ($path) {
            //verifica se foi pasado uma / por parametro, caso foi remove, se não passa o path direto
            $pathF = ($path[0] == "/" ? mb_substr($path, 1) : $path);
            //retornando a url base concatenada com uma barra e o caminho do path
            return CONF_URL_TEST . "/themes/{$theme}/" . $pathF;
        }

        return CONF_URL_TEST . "/themes/{$theme}";
    }

    //ja que não estamos em URL local, vamos retornar um endereço do servidor
    if ($path) {
        //verifica se foi pasado uma / por parametro, caso foi remove, se não passa o path direto
        $pathF = ($path[0] == "/" ? mb_substr($path, 1) : $path);
        //retornando a url base concatenada com uma barra e o caminho do path
        return CONF_URL_BASE . "/themes/{$theme}/" . $pathF;
    }
    return CONF_URL_BASE . "/themes/{$theme}";
}

/**
 * @param string $image
 * @param int $width
 * @param int|null $height
 * @return string
 */
function image(string $image, int $width, int $height = null): string
{
    return url() . "/" . (new \Source\Support\Thumb())->make($image, $width, $height);
}


/**################
 * ###   DATA   ###
 */################

/**METODO PARA FORMATAÇÃO DE DATA
 * @param string $date
 * @param string $format
 * @return string
 * @throws Exception
 */
function date_fmt(string $date = "now", string $format = "d/m/Y H\hi"): string
{
    return (new DateTime($date))->format($format);
}

/**METODO PARA FORMATAÇÃO DE DATA PARA BR
 * @param string $date
 * @return string
 * @throws Exception
 */
function date_fmt_br(string $date = "now"): string
{
    return (new DateTime($date))->format(CONF_DATE_BR);
}

/**METODO PARA FORMATAÇÃO DE DATA COM A DATA DO APLICATIVO
 * @param string $date
 * @return string
 * @throws Exception
 */
function date_fmt_app(string $date = "now"): string
{
    return (new DateTime($date))->format(CONF_DATE_APP);
}


/**################
 * ###   CORE   ###
 */################

/**METODO PARA RETORNAR A ISTANCIA DE UMA CONEXÃO
 * @return PDO
 */
function db(): PDO
{
    return \Source\Core\Connect::getInstance();
}

/**METODO PARA RETORNAR A ISTANCIA DA MESSAGE
 * @return \Source\Core\Message
 */
function message(): \Source\Core\Message
{
    return new \Source\Core\Message();
}

/**METODO PARA RETORNAR UMA ISTANCIA DA SESSÃO
 * @return \Source\Core\Session
 */
function session(): \Source\Core\Session
{
    return new \Source\Core\Session();
}

