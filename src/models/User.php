<?php

namespace src\models;

class User {
    private $email;
    private $password;
    private $username;

    public function __construct(string $email, string $password, string $username) {
        $this->email = $email;
        $this->password = $password;
        $this->username = $username;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getUsername(): string {
        return $this->username;
    }
}