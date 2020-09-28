<?php

//verificando estamos em ambiente de teste
if (strpos(url(), "localhost")) {
    /**
     * CSS
     */
    //iniciando o componente de minificação
    $minCSS = new MatthiasMullie\Minify\CSS();

    //adicionando os arquivos para minificar
    $minCSS->add(__DIR__ . "/../../../shared/styles/styles.css");
    $minCSS->add(__DIR__ . "/../../../shared/styles/boot.css");

    //theme CSS
    //buscando a pasta css dentro da minha pasta de temas
    $cssDir = scandir(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/css");

    //vamos passar por todos os diretorios encontrados na pasta
    foreach ($cssDir as $css) {
        $cssFile = __DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/css/{$css}";

        //buscar somente aquele que tem o .css e adicionando para minificar
        if (is_file($cssFile) && pathinfo($cssFile)['extension'] == "css") {
            $minCSS->add($cssFile);
        }
    }

    //Minify CSS
    //minificando o arquivo e direcionando para a pasta onde sera salvo
    $minCSS->minify(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/style.css");


    /*
     * JS
     */
    $minJS = new MatthiasMullie\Minify\JS();
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery.min.js");
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery.form.js");
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery-ui.js");
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery.mask.js");
    $minJS->add(__DIR__ . "/../../../shared/scripts/highcharts.js");


    //theme JS
    $jsDir = scandir(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/js");
    foreach ($jsDir as $js) {
        $jsFile = __DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/js/{$js}";

        if (is_file($jsFile) && pathinfo($jsFile)['extension'] == "js") {
            $minJS->add($jsFile);
        }
    }

    //Minify JS
    $minJS->minify(__DIR__ . "/../../../themes/" . CONF_VIEW_APP . "/assets/scripts.js");

}