<?php
session_start();
require_once 'Database/config.php';

// Pārbaudīt, vai lietotājs ir pieteicies
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;

// Pārliecināties, vai ir norādīts ieraksta ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Nav norādīts ieraksta ID. <a href='forums.php'>Doties atpakaļ</a>");
}

$post_id = (int)$_GET['id'];

try {
    // Savienojums ar datu bāzi
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Iegūt ieraksta detaļas
    $stmt = $pdo->prepare("SELECT * FROM forum_posts WHERE id = :id");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Ieraksts nav atrasts. <a href='forums.php'>Doties atpakaļ</a>");
    }

    // Apstrādāt jaunas atbildes iesniegšanu
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $logged_in) {
        $reply = trim($_POST['reply']);
        if (!empty($reply)) {
            $stmt = $pdo->prepare("INSERT INTO forum_replies (post_id, username, reply) VALUES (:post_id, :username, :reply)");
            $stmt->execute([
                ':post_id' => $post_id,
                ':username' => $username,
                ':reply' => $reply
            ]);
            header("Location: forum_post.php?id=$post_id");
            exit;
        } else {
            $error = "Atbilde nedrīkst būt tukša.";
        }
    }

    // Iegūt visas atbildes uz šo ierakstu
    $stmt = $pdo->prepare("SELECT * FROM forum_replies WHERE post_id = :post_id ORDER BY created_at DESC");
    $stmt->execute([':post_id' => $post_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Datu bāzes kļūda: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foruma ieraksts</title>
    <link rel="stylesheet" href="css/forum_post.css">
</head>
<body>
    <header>
        <h1>Forums</h1>
    </header>
    <main>
        <section class="post-header">
            <a href="forums.php" class="back-btn">Atpakaļ</a>
            <p class="post-title"><strong><?php echo htmlspecialchars($post['title']); ?></strong></p>
        </section>
        <section class="post-details">
            <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <p class="post-meta">
                <strong><?php echo htmlspecialchars($post['username']); ?></strong> - 
                <span><?php echo htmlspecialchars($post['created_at']); ?></span>
            </p>
        </section>

        <section class="replies-section">
            <?php if ($logged_in): ?>
                <form method="post" class="reply-form">
                    <textarea name="reply" rows="4" placeholder="Pievieno savu atbildi..." required></textarea>
                    <button type="submit" class="btn-reply">Pievienot</button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="login-prompt">Nepieciešams <a href="login.php">Pierakstīties</a> lai pievienotu atbildi.</p>
            <?php endif; ?>

            <h2>Atbildes</h2>
            <?php if (!empty($replies)): ?>
                <ul class="replies-list">
                    <?php foreach ($replies as $reply): ?>
                        <li class="reply">
                            <div class="reply-meta">
                                <strong><?php echo htmlspecialchars($reply['username']); ?></strong> - 
                                <span><?php echo htmlspecialchars($reply['created_at']); ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($reply['reply'])); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-replies">Vēl nav nevienas atbildes. Esi pirmais, kas atbild!</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
