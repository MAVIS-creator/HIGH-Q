<?php
// src/Models/User.php
class User {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Find by email
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // Update last login
    public function updateLastLogin($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
}
