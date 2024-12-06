<?php
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $logged_in ? $_SESSION['username'] : null;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
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
                        <span>Sveiki, <?php echo htmlspecialchars($username); ?>!</span>
                        <a href="index.php?logout=true" class="logout-btn">Izrakstīties</a>
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
            <?php if ($logged_in): ?>
                <a href="new_event.php" class="new-event-btn">Jauns Pasākums</a>
            <?php endif; ?>
        </div>

        <div class="search-bar">
            <input type="text" placeholder="Meklēt pasākumus...">
        </div>

        <div class="event-grid">
            <?php
            // Sample events
            $events = [
                ["title" => "Burvju Šovs", "image" => "images/magic_show.jpg", "price" => "€50", "desc" => "Interaktīvi burvju triki bērniem."],
                ["title" => "Sejas Apgleznošana", "image" => "images/face_painting.jpg", "price" => "€30", "desc" => "Radošās sejas apgleznošanas nodarbības."],
                ["title" => "Āra Spēles", "image" => "images/outdoor_games.jpg", "price" => "€70", "desc" => "Jautras un aizraujošas āra spēles."],
                ["title" => "Māksla un Rokdarbi", "image" => "images/art_craft.jpg", "price" => "€40", "desc" => "Radošas mākslas un rokdarbu aktivitātes."],
            ];

            foreach ($events as $event) {
                echo "
                <div class='event-card'>
                    <img src='{$event['image']}' alt='{$event['title']}'>
                    <h3>{$event['title']}</h3>
                    <p>{$event['desc']}</p>
                    <span class='price'>{$event['price']}</span>
                    <button>Rezervēt</button>
                </div>
                ";
            }
            ?>
        </div>
    </main>

    <!-- Footer Section -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Children's Event Hosting</p>
    </footer>
</body>
</html>
