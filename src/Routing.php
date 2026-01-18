<?php

class Routing {
    public static $routes = []; // Tutaj będziemy trzymać listę adresów

    // Metoda do dodawania tras typu GET
    public static function get($url, $view) {
        self::$routes[$url] = $view;
    }

    // Metoda do dodawania tras typu POST (formularze)
    public static function post($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function run($url) {
        // Czyścimy URL z niepotrzebnych znaków
        $action = explode("/", $url)[0];

        if (!array_key_exists($action, self::$routes)) {
            die("Strona nie istnieje! (404)");
        }

        // Pobieramy nazwę kontrolera przypisanego do trasy
        $controller = self::$routes[$action];
        $object = new $controller;
        $action = $action ?: 'index'; // Jeśli adres to /, odpalamy index

        $object->$action();
    }

    
}