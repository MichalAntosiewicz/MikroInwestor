<?php

session_start();

require_once 'src/Routing.php';
require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/ProjectController.php';

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::get('login', 'SecurityController');
Routing::post('login', 'SecurityController');
Routing::get('register', 'SecurityController');
Routing::post('register', 'SecurityController');


Routing::get('dashboard', 'ProjectController');
Routing::get('market', 'ProjectController');
Routing::get('history', 'ProjectController');
Routing::get('portfolio', 'ProjectController');
Routing::get('asset', 'ProjectController');
Routing::get('assetData', 'ProjectController');
Routing::get('logout', 'ProjectController');

Routing::get('trade', 'ProjectController');
Routing::post('executeTrade', 'ProjectController');

Routing::run($path);