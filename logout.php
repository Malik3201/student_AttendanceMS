<?php
require_once 'includes/header.php';

logout();

// Redirect to home page after logout
header('Location: index.php');
exit;
?>