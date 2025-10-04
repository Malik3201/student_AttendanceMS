<?php
require_once 'auth.php';

function require_login() {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_role($roles) {
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles)) {
        header('Location: dashboard.php?denied=1');
        exit;
    }
}
