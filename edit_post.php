<?php
session_start();
require_once 'Database/config.php';

// Pārbaudīt, vai lietotājs ir pieteicies
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;

if (!$logged_in) {
    die("Jums jābūt pieteikušamies, lai rediģētu ierakstu. <a href='login.php'>Pieslēgties</a>");
}

// Pārbaudīt, vai ir norādīts ieraksta ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Nav norādīts ieraksta ID. <a href='forums.php'>Doties atpakaļ</a>");
}

$post_id = (int)$_GET['id'];

try {
    // Savienojums ar datu bāzi
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Iegūstam ieraksta detaļas
    $stmt = $pdo->prepare("SELECT * FROM forum_posts WHERE id = :id");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Ierasksts nav atrasts. <a href='forums.php'>Doties atpakaļ</a>");
    }

    // Pārliecināmies, ka pieteicies lietotājs ir ieraksta autors
    if ($post['username'] !== $username) {
        die("Jums nav atļauja reģidēt šo rakstu. <a href='forums.php'>Iet atpakaļ</a>");
    }

    // Apstrādāt ieraksta labošanu
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
    die("Datu bāzes kļūda: " . $e->getMessage());
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
