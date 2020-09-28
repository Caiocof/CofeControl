<?php


namespace Source\Models\CoffeeApp;


use Source\Core\Model;

class AppCategory extends Model
{

    public function __construct()
    {
        parent::__construct("app_categories", ["id"], ["name", "type"]);
    }

}