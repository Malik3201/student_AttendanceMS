<?php
require_once 'includes/auth.php';

$user = current_user();
if ($user) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
