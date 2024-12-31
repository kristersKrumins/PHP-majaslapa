<?php
session_start();
require_once 'Database/config.php';

// Check if user is logged in and is not admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
if (isset($_SESSION['admin']) && $_SESSION['admin'] === 1) {
    echo json_encode(['success' => false, 'error' => 'Admins do not mark user notifications']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mark all user notifications as seen
    $upd = $pdo->prepare("
        UPDATE notifications
           SET seen_by_user = 1
         WHERE user_id = :uid
           AND seen_by_user = 0
    ");
    $upd->execute([':uid' => $user_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
