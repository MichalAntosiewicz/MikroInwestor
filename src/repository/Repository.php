<?php

require_once __DIR__.'/../database/Database.php';

use src\database\Database;

abstract class Repository {
    protected $database;

    public function __construct() {
        // Pobieramy instancję Singletona bazy danych
        $this->database = Database::getInstance();
    }
}