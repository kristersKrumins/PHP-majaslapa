<?php
session_start();
require_once 'Database/config.php';

// Check if the user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;

try {
    // Database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle post deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && $logged_in) {
        $post_id_to_delete = (int)$_POST['delete_post'];

        // Verify ownership of the post
        $stmt = $pdo->prepare("SELECT username FROM forum_posts WHERE id = :id");
        $stmt->execute([':id' => $post_id_to_delete]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post && $post['username'] === $username) {
            $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = :id");
            $stmt->execute([':id' => $post_id_to_delete]);
            header("Location: forums.php");
            exit;
        } else {
            $error = "You are not authorized to delete this post.";
        }
    }

    // Handle new post submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content']) && $logged_in) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("INSERT INTO forum_posts (username, title, content) VALUES (:username, :title, :content)");
            $stmt->execute([
                ':username' => $username,
                ':title' => $title,
                ':content' => $content
            ]);
            header("Location: forums.php");
            exit;
        } else {
            $error = "Title and content cannot be empty.";
        }
    }

    // Fetch all posts
    $stmt = $pdo->query("SELECT * FROM forum_posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums</title>
    <link rel="stylesheet" href="css/forums.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toggle dropdown menu
            document.querySelectorAll('.dropdown-btn').forEach(btn => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const dropdownMenu = btn.nextElementSibling;
                    dropdownMenu.classList.toggle('show');
                });
            });

            // Close dropdown menu on outside click
            document.addEventListener('click', () => {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            });
        });
    </script>
</head>
<body>
    <header>
        <!-- Top Bar -->
        <div class="top-bar">
            <img src="images/logo.png" alt="Website Logo" class="logo">
        <div class="user-info">
            <?php if ($logged_in): ?>
                <a href="logout.php" class="logout-btn">Logout</a>
                <span>User: <?php echo htmlspecialchars($username); ?></span>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
        </div>
    </div>

        <!-- Logo Bar -->
        <div class="logo-bar">
            <h1>Forums</h1>
        </div>

        <!-- Navigation Bar -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="forums.php">Forums</a></li>
                <li><a href="Galerie.php">Galerija</a></li>
                <li><a href="contact.php">Kontakti</a></li>
             </ul>
        </nav>

    </header>

    <main>
        <section class="new-post-section">
            <div class="heading-container">
                <h2>Izveido jaunu ierakstu</h2>
            </div>
            <?php if ($logged_in): ?>
                <form method="post" class="new-post-form">
                    <input type="text" name="title" placeholder="Raksta virsraksts" required>
                    <textarea name="content" rows="4" placeholder="Ieraksti savas domas ..." required></textarea>
                    <button type="submit">Ievietot</button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Nepieciešams <a href="login.php">pierakstīties</a> , lai izveidotu ierakstu.</p>
            <?php endif; ?>
        </section>

        <section class="posts-section">
            <h2>Visi ieraksti</h2>
            <?php if (!empty($posts)): ?>
                <ul class="posts-list">
                    <?php foreach ($posts as $post): ?>
                        <li>
                            <div class="post-header">
                                <h3><a href="forum_post.php?id=<?php echo htmlspecialchars($post['id']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                                <div class="dropdown">
                                    <button class="dropdown-btn">⋮</button>
                                    <div class="dropdown-menu">
                                        <form method="post" class="dropdown-form">
                                            <input type="hidden" name="delete_post" value="<?php echo htmlspecialchars($post['id']); ?>">
                                            <button type="submit" class="dropdown-item">Dzēst</button>
                                        </form>
                                        <a href="edit_post.php?id=<?php echo htmlspecialchars($post['id']); ?>" class="dropdown-item">Reģidēt</a>
                                    </div>
                                </div>
                            </div>

                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Vēl nav neviena ieraksta. Esi pirmais, kas padalās ar savām domām!</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
