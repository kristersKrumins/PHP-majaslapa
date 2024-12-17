<?php
session_start();
require_once 'Database/config.php';

if (!isset($_GET['id'])) {
    die("No event ID provided. <a href='index.php'>Go back</a>");
}

$event_id = (int)$_GET['id'];
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 0;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE ID = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found. <a href='index.php'>Go back</a>");
    }

    $eventFolder = 'images/event_' . $event_id . '/';
    $images = glob($eventFolder . '*');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($logged_in && (int)$admin === 1 && isset($_POST['delete'])) {
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM events WHERE ID = :id");
        $delete_stmt->execute([':id' => $event_id]);
        
        foreach ($images as $image) {
            if (file_exists($image)) {
                unlink($image);
            }
        }

        header("Location: index.php?success=2");
        exit;
    } catch (PDOException $e) {
        die("Error deleting event: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></title>
    <link rel="stylesheet" href="css/events.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const images = document.querySelectorAll('.image-slider img');
            const dots = document.querySelectorAll('.slider-dots span');
            const arrowLeft = document.querySelector('.arrow-left');
            const arrowRight = document.querySelector('.arrow-right');
            let currentIndex = 0;

            function showImage(index) {
                images.forEach((img, i) => {
                    img.classList.toggle('active', i === index);
                });

                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            }

            arrowLeft.addEventListener('click', function () {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                showImage(currentIndex);
            });

            arrowRight.addEventListener('click', function () {
                currentIndex = (currentIndex + 1) % images.length;
                showImage(currentIndex);
            });

            // Initialize
            showImage(currentIndex);
        });
    </script>
</head>
<body>
    <header>
    </header>
    <main>
        <div class="event-details">
            <div class="image-slider">
                <span class="arrow-left">❮</span>
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo $image; ?>" alt="Event Image">
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No images available for this event.</p>
                <?php endif; ?>
                <span class="arrow-right">❯</span>
            </div>
            <div class="slider-dots">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $index => $image): ?>
                        <span data-index="<?php echo $index; ?>"></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <h2>Nosaukums</h2>
            <p><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></p>
            <h2>Apraksts</h2>
            <p><?php echo htmlspecialchars($event['APRAKSTS']); ?></p>
            <h2>Cena</h2>
            <p>€<?php echo htmlspecialchars($event['CENA']); ?></p>

            <div class="action-buttons">
                <a href="index.php" class="back-btn">Atpakaļ</a>
                <?php if ($logged_in && (int)$admin === 1): ?>
                    <a href="edit_event.php?id=<?php echo htmlspecialchars($event['ID']); ?>" class="edit-btn">Reģidēt</a>
                    <form method="post" class="delete-form">
                        <button type="submit" name="delete" class="delete-btn">Dzēst pasākumu</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
