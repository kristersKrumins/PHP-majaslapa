<?php
session_start();
require_once 'Database/config.php';

// Pārbaudīt, vai lietotājs ir pieteicies un nav administrators
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Lietotājs nav pieteicies']);
    exit;
}
if (isset($_SESSION['admin']) && $_SESSION['admin'] === 1) {
    echo json_encode(['success' => false, 'error' => 'Administratoriem nav nepieciešams atzīmēt lietotāja paziņojumus']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Atzīmēt visus lietotāja paziņojumus kā redzētus
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
