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
            let currentIndex = 0;

            function showImage(index) {
                images.forEach((img, i) => {
                    img.classList.toggle('active', i === index);
                });
            }

            document.querySelector('.nav-button-left').addEventListener('click', function () {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                showImage(currentIndex);
            });

            document.querySelector('.nav-button-right').addEventListener('click', function () {
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
        <h1><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></h1>
    </header>
    <main>
        <div class="event-details">
            <div class="image-slider">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo $image; ?>" alt="Event Image">
                    <?php endforeach; ?>
                    <img src="images/button2.png" class="nav-button nav-button-left" alt="Previous">
                    <img src="images/button.png" class="nav-button nav-button-right" alt="Next">
                <?php else: ?>
                    <p>No images available for this event.</p>
                <?php endif; ?>
            </div>
            <h2>Description</h2>
            <p><?php echo htmlspecialchars($event['APRAKSTS']); ?></p>
            <h2>Price</h2>
            <p>€<?php echo htmlspecialchars($event['CENA']); ?></p>

            <?php if ($logged_in && (int)$admin === 1): ?>
                <form method="post">
                    <button type="submit" name="delete" class="delete-btn">Delete Event</button>
                </form>
            <?php endif; ?>
        </div>
        <a href="index.php" class="back-btn">Back</a>
    </main>
</body>
</html>
