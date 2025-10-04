<?php
require_once 'db.php';

function login($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        return true;
    }
    return false;
}

function current_user() {
    if (isset($_SESSION['uid']) && isset($_SESSION['role']) && isset($_SESSION['name'])) {
        return [
            'id' => $_SESSION['uid'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name']
        ];
    }
    return null;
}

function logout() {
    session_destroy();
    session_start();
}
