<?php

namespace Source\Core;

use \PDO;
use \PDOException;

class Connect
{
  private const OPTIONS = [
    //muda dotos os caracteres para o padrao utf8
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    //sempre que ocorrer um erro lança uma exeção
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //transforma todo resultando das query em um objeto
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    //transforma todos os dados da tabela em natural
    PDO::ATTR_CASE => PDO::CASE_NATURAL
  ];

  //variavel que vai permitir conectar ao banco
  private static $instance;

  public static function getInstance(): PDO
  {
    //verifica se existe uma conexao
    if (empty(self::$instance)) {
      //caso não exista, cria uma
      try {
        self::$instance = new PDO(
          "mysql:host=" . CONF_DB_HOST . ";dbname=" . CONF_DB_NAME,
          CONF_DB_USER,
          CONF_DB_PASS,
          self::OPTIONS
        );
      } catch (PDOException $exception) {
        die("<h1 class='trigger error'>Whoops! Erro ao conectar...</h1>");
      }
    }
    return self::$instance;
  }

  //mantendo a classe seguro para que não seja alterada em caso de
  //heralça ou atribuição
  final private function __construct()
  {
  }


  final private function __clone()
  {
  }
}
