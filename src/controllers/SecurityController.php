<?php

require_once 'AppController.php';
require_once __DIR__.'/../models/User.php';
require_once __DIR__.'/../repository/UserRepository.php';

class SecurityController extends AppController {

    public function login() {
        $userRepository = new UserRepository();

        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = $userRepository->getUser($email);

        if (!$user) {
            return $this->render('login', ['messages' => ['Użytkownik o tym adresie nie istnieje!']]);
        }

        if (!password_verify($password, $user->getPassword())) {
            return $this->render('login', ['messages' => ['Błędne hasło!']]);
        }

        $_SESSION['user_id'] = $user->getId(); 

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
        exit();
    }

    public function register() {
        if (!$this->isPost()) {
            return $this->render('register');
        }

        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirmedPassword = $_POST['confirmedPassword'] ?? null;

        if (strlen($password) < 4) {
            return $this->render('register', ['messages' => ['Hasło jest zbyt krótkie! (min. 4 znaki)']]);
        }

        $userRepository = new UserRepository();
        
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $user = new \src\models\User($email, password_hash($password, PASSWORD_BCRYPT), $username);
            $userRepository->addUser($user);

            return $this->render('login', ['messages' => ['Rejestracja udana! Możesz się zalogować.']]);

        } catch (\PDOException $e) {
            if ($e->getCode() == '23505') {
                return $this->render('register', ['messages' => ['Email lub nazwa użytkownika jest już zajęta!']]);
            }
            return $this->render('register', ['messages' => ['Błąd bazy danych: ' . $e->getMessage()]]);
        }
    }
}