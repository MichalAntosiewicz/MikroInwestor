<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

use src\models\User;

class UserRepository extends Repository {

    public function getUser(string $email): ?User {
        $stmt = $this->database->getConnection()->prepare('
            SELECT * FROM public.users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jeśli nie znaleźliśmy gościa w bazie
        if ($user == false) {
            return null;
        }

        // Zwracamy obiekt modelu User, żeby kontroler miał na czym pracować
        return new User(
            $user['email'],
            $user['password'],
            $user['username']
        );
    }

    public function addUser(User $user) {
    $stmt = $this->database->getConnection()->prepare('
        INSERT INTO public.users (username, email, password)
        VALUES (?, ?, ?)
    ');

    $stmt->execute([
        $user->getUsername(),
        $user->getEmail(),
        $user->getPassword() // Na razie tekst jawny, dla ułatwienia testów
    ]);
}
}