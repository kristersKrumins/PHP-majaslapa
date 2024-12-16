<?php
session_start();

// Include database configuration
require_once 'Database/config.php';

// Check if user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;
$admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 0;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_unset();
    header("Location: index.php");
    exit;
}

// Database connection using the constants from config.php
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch events from the database
try {
    $stmt = $pdo->query("SELECT * FROM events");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch events: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Children's Event Hosting</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const successBanner = document.getElementById('success-banner');
            if (successBanner) {
                setTimeout(() => {
                    successBanner.style.opacity = '0'; // Fade out
                    setTimeout(() => {
                        successBanner.style.display = 'none'; // Remove after fade
                    }, 1000);
                }, 5000); // Show duration
            }
        });
    </script>
    <style>
        #success-banner {
            transition: opacity 1s ease-out; /* Smooth fade-out */
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="right-info">
                <?php if ($logged_in): ?>
                    <div class="user-info">
                        <a href="index.php?logout=true" class="logout-btn">Logout</a>
                        <span>User: <?php echo htmlspecialchars($username); ?></span>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="logo-bar">
            <h1>Children's Event Hosting</h1>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div id="success-banner" class="success-message">Event created successfully!</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div id="success-banner" class="success-message">Event deleted successfully!</div>
        <?php endif; ?>

        <div class="main-heading">
            <h2>Explore Fun Events for Kids!</h2>
            <?php if ($logged_in && (int)$admin === 1): ?>
                <a href="new_event.php" class="new-event-btn">Create New Event</a>
            <?php endif; ?>
        </div>

        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <?php
                $eventFolder = 'images/event_' . $event['ID'] . '/';
                $images = glob($eventFolder . '*');
                $firstImage = $images ? $images[0] : 'placeholder.jpg';
                ?>
                <div class="event-card">
                    <a href="events.php?id=<?php echo htmlspecialchars($event['ID']); ?>">
                        <img src="<?php echo $firstImage; ?>" alt="<?php echo htmlspecialchars($event['NOSAUKUMS']); ?>">
                        <h3><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></h3>
                    </a>
                    <p><?php echo htmlspecialchars($event['APRAKSTS']); ?></p>
                    <span class="price">€<?php echo htmlspecialchars($event['CENA']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Children's Event Hosting</p>
    </footer>
</body>
</html>
