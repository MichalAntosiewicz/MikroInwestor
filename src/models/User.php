<?php
namespace src\models;

class User {
    private $id;
    private $email;
    private $password;
    private $username;
    private $balance;
    private $marketMode;
    private $role;

    public function __construct(
        string $email, 
        string $password, 
        string $username, 
        string $role, // Przeniesione tutaj (wymagany parametr)
        float $balance = 0.0, 
        int $id = null, 
        string $marketMode = 'simulated'
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->username = $username;
        $this->role = $role;
        $this->balance = $balance;
        $this->id = $id;
        $this->marketMode = $marketMode;
    }

    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getUsername() { return $this->username; }
    public function getBalance(): float { return (float)$this->balance; }
    public function getPassword() { return $this->password; }
    public function getRole(): string { return $this->role; }
    public function getMarketMode(): string { return $this->marketMode; }

    public function isAdmin(): bool {
        return $this->role === 'admin';
    }
}