<?php
session_start();
require_once 'Database/config.php';
if (isset($_GET['logout'])) {
    session_destroy();
    session_unset();
    header("Location: forums.php");
    exit;
}
// Pārbaudīt, vai lietotājs ir pieteicies
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username  = $logged_in ? $_SESSION['username'] : null;
// Ja izsekojat administratora statusu
$admin     = isset($_SESSION['admin']) ? (int)$_SESSION['admin'] : 0;

try {
    // Savienojums ar datu bāzi
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Apstrādāt ieraksta dzēšanu
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && $logged_in) {
        $post_id_to_delete = (int)$_POST['delete_post'];

        // Iegūt ieraksta lietotājvārdu, lai pārbaudītu īpašumtiesības
        $stmt = $pdo->prepare("SELECT username FROM forum_posts WHERE id = :id");
        $stmt->execute([':id' => $post_id_to_delete]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            // Pārbaudīt, vai lietotājs ir ieraksta autors VAI administrators
            if ($post['username'] === $username || $admin === 1) {
                $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = :id");
                $stmt->execute([':id' => $post_id_to_delete]);
                header("Location: forums.php");
                exit;
            } else {
                $error = "Jums nav atļauts dzēst šo ierakstu.";
            }
        }
    }

    // Apstrādāt jaunu ierakstu izveidi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content']) && $logged_in) {
        $title   = trim($_POST['title']);
        $content = trim($_POST['content']);

        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("
                INSERT INTO forum_posts (username, title, content)
                VALUES (:username, :title, :content)
            ");
            $stmt->execute([
                ':username' => $username,
                ':title'    => $title,
                ':content'  => $content
            ]);
            header("Location: forums.php");
            exit;
        } else {
            $error = "Virsraksts un saturs nedrīkst būt tukšs.";
        }
    }

    // Iegūt visus ierakstus
    $stmt = $pdo->query("SELECT * FROM forum_posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Datu bāzes kļūda: " . $e->getMessage());
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
            // Pārslēgt nolaižamo izvēlni
            document.querySelectorAll('.dropdown-btn').forEach(btn => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const dropdownMenu = btn.nextElementSibling;
                    dropdownMenu.classList.toggle('show');
                });
            });

            // Aizvērt nolaižamo izvēlni, ja noklikšķina ārpus tās
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
        <!-- Augšējā josla -->
        <div class="top-bar">
            <img src="images/logo.png" alt="Website Logo" class="logo">
            <div class="user-info">
                <?php if ($logged_in): ?>
                    <a href="forums.php?logout=1" class="logout-btn">Izrakstīties</a>
                    <span>Lietotājs: <?php echo htmlspecialchars($username); ?></span>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Pierakstīties</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logo josla -->
        <div class="logo-bar">
            <h1>Forums</h1>
        </div>

        <!-- Navigācijas josla -->
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
                <p>Nepieciešams <a href="login.php">pierakstīties</a>, lai izveidotu ierakstu.</p>
            <?php endif; ?>
        </section>

        <section class="posts-section">
            <h2>Visi ieraksti</h2>
            <?php if (!empty($posts)): ?>
                <ul class="posts-list">
                    <?php foreach ($posts as $post): ?>
                        <li>
                            <div class="post-header">
                                <h3>
                                    <a href="forum_post.php?id=<?php echo htmlspecialchars($post['id']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <?php
                                // Rādīt nolaižamo izvēlni tikai, ja lietotājs ir pieteicies UN lietotājs ir autors vai administrators
                                if ($logged_in && ($admin === 1 || $post['username'] === $username)) {
                                    ?>
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
                                <?php } ?>
                            </div>
                            <!-- Ja vēlaties, šeit var parādīt ieraksta fragmentu vai kopsavilkumu -->
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
