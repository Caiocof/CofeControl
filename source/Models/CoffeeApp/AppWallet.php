<?php


namespace Source\Models\CoffeeApp;


use Source\Core\Model;

class AppWallet extends Model
{

    public function __construct()
    {
        parent::__construct("app_wallets", ["id"], ["user_id", "wallet"]);
    }

}