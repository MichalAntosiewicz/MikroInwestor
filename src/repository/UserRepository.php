<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

use src\models\User;

// BINGO D1: Klasa korzysta z Database (Singleton) poprzez dziedziczenie z klasy bazowej Repository
class UserRepository extends Repository {

    public function getUserById(int $id): ?User {
        $stmt = $this->database->getConnection()->prepare('
            SELECT * FROM public.users WHERE id = :id
        ');
        // BINGO A1: Prepared Statements
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        return new User(
            $user['email'],
            $user['password'],
            $user['username'],
            (float)$user['balance'],
            (int)$user['id'],
            $user['market_mode'] ?? 'simulated'
        );
    }

    public function getUser(string $email): ?User {
        $stmt = $this->database->getConnection()->prepare('
            SELECT * FROM public.users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;

        return new User(
            $user['email'],
            $user['password'],
            $user['username'],
            (float)$user['balance'],
            (int)$user['id'],
            $user['market_mode'] ?? 'simulated'
        );
    }

    public function addUser(User $user) {
        $stmt = $this->database->getConnection()->prepare('
            INSERT INTO public.users (username, email, password, balance, market_mode)
            VALUES (:u, :e, :p, :b, :m)
        ');

        $stmt->execute([
            'u' => $user->getUsername(),
            'e' => $user->getEmail(),
            'p' => $user->getPassword(),
            'b' => 151401, // Twoja kwota startowa
            'm' => 'simulated'
        ]);
    }
}