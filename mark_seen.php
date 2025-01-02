<?php
session_start();
require_once 'Database/config.php';

// Pārbaudīt, vai lietotājs ir pieteicies
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success'=>false, 'error'=>'Lietotājs nav pieteicies']);
    exit;
}
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Atzīmēt paziņojumus kā redzētus (seen=1)
    $upd = $pdo->prepare("
        UPDATE notifications
           SET seen=1
         WHERE user_id = :uid
           AND seen=0
    ");
    $upd->execute([':uid'=>$user_id]);

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
