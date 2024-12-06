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
                <a href="login.php" class="login-btn">Log In</a>
            </div>
        </div>
        <!-- Logo Section -->
        <div class="logo-bar">
            <h1>Children's Event Hosting</h1>
        </div>
        <!-- Navigation Bar -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="main-heading">
            <h2>Discover Fun-Filled Events for Children!</h2>
            <a href="new_event.php" class="new-event-btn">Jauns Pasākums</a>
        </div>

        <div class="search-bar">
            <input type="text" placeholder="Search for events...">
        </div>

        <div class="event-grid">
            <?php
            // Sample events
            $events = [
                ["title" => "Magic Show", "image" => "images/magic_show.jpg", "price" => "€50", "desc" => "Interactive magic tricks for kids."],
                ["title" => "Face Painting", "image" => "images/face_painting.jpg", "price" => "€30", "desc" => "Creative face painting sessions."],
                ["title" => "Outdoor Games", "image" => "images/outdoor_games.jpg", "price" => "€70", "desc" => "Fun and exciting outdoor games."],
                ["title" => "Art & Craft", "image" => "images/art_craft.jpg", "price" => "€40", "desc" => "Creative art and craft activities."],
            ];

            foreach ($events as $event) {
                echo "
                <div class='event-card'>
                    <img src='{$event['image']}' alt='{$event['title']}'>
                    <h3>{$event['title']}</h3>
                    <p>{$event['desc']}</p>
                    <span class='price'>{$event['price']}</span>
                    <button>Book Now</button>
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
