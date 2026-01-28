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
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;

        return new User(
            $user['email'],
            $user['password'],
            $user['username'],
            $user['role'] ?? 'user', // ROLA dodana jako 4. argument
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
            $user['role'] ?? 'user', // ROLA dodana jako 4. argument
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


    public function getAllUsers(): array {
        $stmt = $this->database->getConnection()->prepare('
            SELECT * FROM public.users ORDER BY id ASC
        ');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $users = [];
        foreach ($rows as $row) {
            // BINGO A1: Rzutowanie typów zapobiega TypeError przy ścisłym typowaniu modelu
            $users[] = new User(
                (string)$row['email'], 
                (string)$row['password'], 
                (string)$row['username'], 
                (string)($row['role'] ?? 'user'), 
                (float)$row['balance'], 
                (int)$row['id'],           // JAWNE RZUTOWANIE NA INT
                (string)($row['market_mode'] ?? 'simulated')
            );
        }
        return $users;
    }

    public function updateMarketMode(int $userId, string $mode) {
        $stmt = $this->database->getConnection()->prepare('UPDATE public.users SET market_mode = :m WHERE id = :id');
        $stmt->execute(['m' => $mode, 'id' => $userId]);
    }

    public function deleteUser(int $id): bool {
        $stmt = $this->database->getConnection()->prepare('
            DELETE FROM public.users WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}