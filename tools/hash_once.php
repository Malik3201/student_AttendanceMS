<?php
require_once '../includes/db.php';

// Find users with placeholder hash pattern
$stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE password_hash LIKE '__HASH_%'");
$stmt->execute();
$users = $stmt->fetchAll();

$updated = 0;
foreach ($users as $user) {
    $newHash = password_hash('secret123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $user['id']]);
    $updated++;
}

echo "N users updated: " . $updated;
