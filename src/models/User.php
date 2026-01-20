<?php
namespace src\models;

class User {
    private $id;
    private $email;
    private $password;
    private $username;
    private $balance;
    private $marketMode; // NOWE POLE

    public function __construct(
        string $email, 
        string $password, 
        string $username, 
        float $balance = 0.0, 
        int $id = null, 
        string $marketMode = 'simulated' // NOWY ARGUMENT
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->username = $username;
        $this->balance = $balance;
        $this->id = $id;
        $this->marketMode = $marketMode;
    }

    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getUsername() { return $this->username; }
    public function getBalance(): float { return (float)$this->balance; }
    public function getPassword() { return $this->password; }
    
    // NOWY GETTER
    public function getMarketMode(): string { 
        return $this->marketMode; 
    }
}