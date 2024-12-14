<?php
session_start();

// Include database configuration
require_once 'Database/config.php';

// Check if user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;
$admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 0; // Assume non-admin (0) if not set

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_unset(); // Clear session variables
    header("Location: index.php"); // Redirect to refresh page
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
</head>
<body>
    <!-- Header Section -->
    <header>
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="right-info">
                <?php if ($logged_in): ?>
                    <!-- Display username and logout button -->
                    <div class="user-info">
                        <a href="index.php?logout=true" class="logout-btn">Izrakstīties</a>
                        <span>Lietotājs: <?php echo htmlspecialchars($username); ?></span>
                    </div>
                <?php else: ?>
                    <!-- Display login button -->
                    <a href="login.php" class="login-btn">Pieslēgties</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Logo Section -->
        <div class="logo-bar">
            <h1>Children's Event Hosting</h1>
        </div>
        <!-- Navigation Bar -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Sākums</a></li>
                <li><a href="events.php">Pasākumi</a></li>
                <li><a href="about.php">Par Mums</a></li>
                <li><a href="contact.php">Kontakti</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="main-heading">
            <h2>Atklājiet jautrus pasākumus bērniem!</h2>
            <?php if ($logged_in && (int)$admin === 1): ?>
                <a href="new_event.php" class="new-event-btn">Jauns Pasākums</a>
            <?php endif; ?>
        </div>

        <div class="search-bar">
            <input type="text" placeholder="Meklēt pasākumus...">
        </div>

        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <img src="<?php echo htmlspecialchars($event['BILDE']); ?>" alt="<?php echo htmlspecialchars($event['NOSAUKUMS']); ?>">
                    <h3><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></h3>
                    <p><?php echo htmlspecialchars($event['APRAKSTS']); ?></p>
                    <span class="price">€<?php echo htmlspecialchars($event['CENA']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Footer Section -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Children's Event Hosting</p>
    </footer>
</body>
</html>
