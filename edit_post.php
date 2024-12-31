<?php
session_start();
require_once 'Database/config.php';

// Check if the user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;

if (!$logged_in) {
    die("You must be logged in to edit a post. <a href='login.php'>Log in</a>");
}

// Check if the post ID is provided
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
        die("Ierasksts nav atrasts. <a href='forums.php'>Doties atpakaļ</a>");
    }

    // Ensure the logged-in user is the creator of the post
    if ($post['username'] !== $username) {
        die("Jums nav atļauja reģidēt šo rakstu. <a href='forums.php'>Iet atpakaļ</a>");
    }

    // Handle post update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("UPDATE forum_posts SET title = :title, content = :content WHERE id = :id");
            $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':id' => $post_id
            ]);
            header("Location: forums.php?success=edit");
            exit;
        } else {
            $error = "Virsraksts un saturs nedrīkst būt tukšs.";
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rediģēt ierakstus</title>
    <link rel="stylesheet" href="css/edit_post.css">
</head>
<body>
    <header>
        <h1>Rediģēt ierakstus</h1>
    </header>
    <main>
        <section class="edit-post-section">
        <a href="forums.php" class="back-btn">Atpakaļ</a>
            <form method="post" class="edit-post-form">
                <label for="title">Ieraksta nosaukums</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

                <label for="content">Ieraksta teksts</label>
                <textarea name="content" id="content" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>

                <button type="submit">Rediģēt ierakstu</button>
            </form>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
