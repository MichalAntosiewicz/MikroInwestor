<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

use src\models\User;

class UserRepository extends Repository {

    public function getUserById(int $id): ?User {
        $stmt = $this->database->getConnection()->prepare('
            SELECT * FROM public.users WHERE id = :id
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        return new \src\models\User(
            $user['email'],
            $user['password'],
            $user['username'],
            (float)$user['balance'],
            (int)$user['id'],
            $user['market_mode']
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
            $user->getPassword() 
        ]);
    }
}