<?php
require_once 'db.php';
require_once 'functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function requireLogin() {
        if (!isLoggedIn()) {
            redirect('login.php');
        }
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$userId]
        );
        
        if ($user && password_verify($oldPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            return $this->db->update(
                'users',
                ['password' => $hashedPassword],
                "id = ?",
                [$userId]
            );
        }
        
        return false;
    }
    
    public function updateProfile($userId, $data) {
        return $this->db->update('users', $data, "id = ?", [$userId]);
    }
}
?>