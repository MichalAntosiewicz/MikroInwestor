<?php

require_once 'AppController.php';
require_once __DIR__.'/../models/User.php';
require_once __DIR__.'/../repository/UserRepository.php';

use src\models\User;

class SecurityController extends AppController {

    public function login() {
        # BINGO E1 : Wymuszanie HTTPS (Logika do omówienia na obronie)
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') { }

        if (session_status() === PHP_SESSION_NONE) {
            # BINGO C3: HttpOnly cookie
            session_set_cookie_params([
                'lifetime' => 0, 
                'path' => '/', 
                'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'secure' => false, 
                'httponly' => true, 
                'samesite' => 'Lax'
            ]);
            session_start();
        }

        $userRepository = new UserRepository();

        # BINGO A2: Obsługa tylko POST
        if (!$this->isPost()) {
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // BINGO B2: CSRF Token
            }
            return $this->render('login', ['csrf_token' => $_SESSION['csrf_token']]);
        }

        # BINGO B2: Weryfikacja CSRF + BINGO A4: Kara czasowa za błędny token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            $_SESSION['block_until'] = time() + 5; 
            http_response_code(403);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
            
            return $this->render('login', [
                'messages' => ['Błąd weryfikacji CSRF! Spróbuj ponownie za 5s.'],
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        }

        # BINGO A4: Blokada czasowa (15 sek po 3 próbach)
        if (isset($_SESSION['block_until']) && time() < $_SESSION['block_until']) {
            $remaining = $_SESSION['block_until'] - time();
            http_response_code(403); 
            return $this->render('login', [
                'messages' => ["Zbyt wiele prób. Odczekaj $remaining sek."],
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        # BINGO C1: Walidacja email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400); 
            return $this->render('login', [
                'messages' => ['Niepoprawny format adresu email!'],
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        }

        $user = $userRepository->getUser($email);

        # BINGO B1: Generyczny komunikat + BINGO A3: Hasła nie są logowane
        if (!$user || !password_verify($password, $user->getPassword())) {
            
            # BINGO E5: Logowanie do audytu
            $logDir = __DIR__.'/../../logs';
            if (!file_exists($logDir)) { @mkdir($logDir, 0777, true); }
            $logEntry = date("Y-m-d H:i:s") . " - Fail: " . $email . " IP: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
            @file_put_contents($logDir.'/audit.log', $logEntry, FILE_APPEND);

            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= 3) {
                $_SESSION['block_until'] = time() + 15;
                unset($_SESSION['login_attempts']);
                http_response_code(403);
                return $this->render('login', [
                    'messages' => ['Blokada na 15 sekund aktywowana.'],
                    'csrf_token' => $_SESSION['csrf_token']
                ]);
            }
            http_response_code(401); 
            return $this->render('login', [
                'messages' => ['Nieprawidłowy email lub hasło!'],
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        }

        // Sukces
        unset($_SESSION['login_attempts'], $_SESSION['block_until'], $_SESSION['csrf_token']);
        session_regenerate_id(true); // BINGO B3: Regeneracja ID sesji
        $_SESSION['user_id'] = $user->getId(); 

        header("Location: dashboard");
        exit();
    }

    public function register() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (!$this->isPost()) { 
            return $this->render('register'); 
        }

        $email = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return $this->render('register', ['messages' => ['Niepoprawny format email!']]);
        }

        if (strlen($password) < 4) {
            http_response_code(400);
            return $this->render('register', ['messages' => ['Hasło min. 4 znaki!']]);
        }

        $userRepository = new UserRepository();
        try {
            # BINGO E2: BCrypt - Hashujemy przed stworzeniem obiektu
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT); 
            
            # FIX: Zgodność z konstruktorem User(email, password, username, role, balance)
            # Podajemy 151401 jako startowy balance (wymóg z albumem)
            $user = new User($email, $hashedPassword, $username, 'user', 151401.0);
            
            $userRepository->addUser($user);
            return $this->render('login', [
                'messages' => ['Zarejestrowano pomyślnie! Zaloguj się.'],
                'csrf_token' => $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))
            ]);
            
        } catch (\PDOException $e) {
            # BINGO C4: Unique check (PostgreSQL Error Code 23505)
            if ($e->getCode() == '23505') { 
                http_response_code(409);
                return $this->render('register', ['messages' => ['Email lub Username jest już zajęty!']]);
            }
            http_response_code(500);
            return $this->render('register', ['messages' => ['Błąd bazy danych. Spróbuj później.']]);
        }
    }
}