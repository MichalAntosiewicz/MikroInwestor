<?php

class Routing {
    public static $routes = [];

    public static function get($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function post($url, $view) {
        self::$routes[$url] = $view;
    }

    public static function run($url) {
        $action = explode("/", $url)[0];

        if (!array_key_exists($action, self::$routes)) {
            die("Strona nie istnieje! (404)");
        }

        $controller = self::$routes[$action];
        $object = new $controller;
        $action = $action ?: 'index';

        $object->$action();
    }

    
}