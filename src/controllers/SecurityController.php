<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController {

    public function login() {
        // 1. Jeśli to nie jest POST, po prostu pokaż stronę logowania
        if (!$this->isPost()) {
            return $this->render('login');
        }

        // 2. Pobierz dane z formularza
        $email = $_POST['email'];
        $password = $_POST['password'];

        // 3. Połącz się z bazą przez Repozytorium
        $userRepository = new UserRepository();
        $user = $userRepository->getUser($email);

        // 4. Walidacja
        if (!$user) {
            return $this->render('login', ['messages' => ['User with this email not found!']]);
        }

        if ($user->getEmail() !== $email) {
            return $this->render('login', ['messages' => ['User with this email not found!']]);
        }

        // UWAGA: W init.sql hasło jest jawne lub zahaszowane. 
        // Na tym etapie sprawdzamy porównaniem stringów (później dodamy password_verify)
        if ($user->getPassword() !== $password) {
            return $this->render('login', ['messages' => ['Wrong password!']]);
        }

        $_SESSION['user_id'] = $user->getEmail(); // Na razie używamy emaila jako identyfikatora
        header("Location: dashboard");

        // 5. Sukces - Przekierowanie na dashboard (który zaraz zrobimy)
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }

    public function register() {
    if (!$this->isPost()) {
        return $this->render('register');
    }

    $email = $_POST['email'];
    $password = $_POST['password'];
    $username = $_POST['username'];

    // 1. Tworzymy obiekt użytkownika
    $user = new src\models\User($email, $password, $username);

    // 2. Zapisujemy w bazie
    $userRepository = new UserRepository();
    $userRepository->addUser($user);

    // 3. Przekierowujemy do logowania z komunikatem
    return $this->render('login', ['messages' => ['Rejestracja udana! Zaloguj się.']]);
}

    
}