<?php
session_start();
require_once 'Database/config.php';

// Check if the user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;

// Ensure the post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("No post ID provided. <a href='forums.php'>Go back</a>");
}

$post_id = (int)$_GET['id'];

try {
    // Database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the post details
    $stmt = $pdo->prepare("SELECT * FROM forum_posts WHERE id = :id");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Post not found. <a href='forums.php'>Go back</a>");
    }

    // Handle new reply submission
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
            $error = "Reply cannot be empty.";
        }
    }

    // Fetch all replies for the post
    $stmt = $pdo->prepare("SELECT * FROM forum_replies WHERE post_id = :post_id ORDER BY created_at DESC");
    $stmt->execute([':post_id' => $post_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Post</title>
    <link rel="stylesheet" href="css/forum_post.css">
</head>
<body>
    <header>
        <h1>Forum Post</h1>
    </header>
    <main>
        <section class="post-header">
            <a href="forums.php" class="back-btn">← Back</a>
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
                    <textarea name="reply" rows="4" placeholder="Write your reply..." required></textarea>
                    <button type="submit" class="btn-reply">Post Reply</button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="login-prompt">You must <a href="login.php">log in</a> to reply.</p>
            <?php endif; ?>

            <h2>Replies</h2>
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
                <p class="no-replies">No replies yet. Be the first to reply!</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

