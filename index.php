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
// Initialize query and parameters
$query = "SELECT * FROM events WHERE 1=1";
$params = [];

// Apply filters if present
if (!empty($_GET['name'])) {
    $query .= " AND NOSAUKUMS LIKE :name";
    $params[':name'] = '%' . $_GET['name'] . '%';
}

if (!empty($_GET['price'])) {
    $query .= " AND CENA <= :price";
    $params[':price'] = (float)$_GET['price'];
}

if (!empty($_GET['min_age']) && !empty($_GET['max_age'])) {
    $query .= " AND VECUMS >= :min_age AND VECUMS2 <= :max_age";
    $params[':min_age'] = (int)$_GET['min_age'];
    $params[':max_age'] = (int)$_GET['max_age'];
}

if (!empty($_GET['gender'])) {
    $query .= " AND DZIMUMS = :gender";
    $params[':gender'] = $_GET['gender'];
}

if (!empty($_GET['category'])) {
    $query .= " AND KATEGORIJA = :category";
    $params[':category'] = $_GET['category'];
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
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
                    // Remove the success parameter from the URL
                    const url = new URL(window.location.href);
                    url.searchParams.delete('success');
                    window.history.replaceState(null, '', url); // Update the URL
                }, 1000);
            }, 5000); // Show duration
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        const minAgeSlider = document.getElementById('min-age');
        const maxAgeSlider = document.getElementById('max-age');
        const minAgeDisplay = document.getElementById('min-age-display');
        const maxAgeDisplay = document.getElementById('max-age-display');
        const sliderTrack = document.querySelector('.slider-track');

        const updateRangeValues = () => {
            const minAge = parseInt(minAgeSlider.value);
            const maxAge = parseInt(maxAgeSlider.value);

            // Prevent overlap
            if (minAge >= maxAge) {
                if (event.target === minAgeSlider) {
                    minAgeSlider.value = maxAge - 1;
                } else {
                    maxAgeSlider.value = minAge + 1;
                }
            }

            // Update display
            minAgeDisplay.textContent = minAgeSlider.value;
            maxAgeDisplay.textContent = maxAgeSlider.value;

            // Update track position and width
            const minPercent = (minAge / minAgeSlider.max) * 100;
            const maxPercent = (maxAge / maxAgeSlider.max) * 100;
            sliderTrack.style.left = `${minPercent}%`;
            sliderTrack.style.width = `${maxPercent - minPercent}%`;
        };

        minAgeSlider.addEventListener('input', updateRangeValues);
        maxAgeSlider.addEventListener('input', updateRangeValues);

        // Initialize values
        updateRangeValues();
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
        
            <img src="images/logo.png" alt="Website Logo" class="logo">
      
        <div class="user-info">
            <?php if ($logged_in): ?>
                <a href="index.php?logout=true" class="logout-btn">Logout</a>
                <span>User: <?php echo htmlspecialchars($username); ?></span>
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
                <li><a href="forums.php">Forums</a></li>
                <li><a href="Galerie.php">Galerija</a></li>
                <li><a href="contact.php">Kontakti</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div id="success-banner" class="success-message">Pasākums izveidot veiksmīgi!</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] == 2): ?>
            <div id="success-banner" class="success-message">Pasākums izdzēsts veiksmīgi!</div>
        <?php endif; ?>

        <div class="main-heading">
            
            <?php if ($logged_in && (int)$admin === 1): ?>
                <a href="new_event.php" class="new-event-btn">Izveidot jaunu pasākumu</a>
            <?php endif; ?>
        </div>
        <div class="filter-form">
            <form method="get" action="index.php">
                <div>
                    <label for="name">Nosaukums:</label>
                    <input type="text" id="name" name="name" placeholder="Pasākuma nosaukums">
                </div>
                
                <div>
                    <label for="price">Cena (līdz):</label>
                    <input type="number" id="price" name="price" placeholder="Max Cena (€)">
                </div>
                
                <div>
                    <label>Vecuma diapazons:</label>
                    <div class="range-slider">
                        <input type="range" id="min-age" name="min_age" min="0" max="100" value="0">
                        <input type="range" id="max-age" name="max_age" min="0" max="100" value="100">
                        <div class="slider-track"></div> <!-- White line between handles -->
                    </div>
                    <p class="range-display">Izvēlētais diapazons: <span id="min-age-display">0</span> - <span id="max-age-display">100</span> gadi</p>
                </div>



                <!-- Kategorija Checkboxes -->
                <div>
                    <label for="category">Kategorija:</label>
                    <div class="category-options">
                        <div class="checkbox-item">
                            <input type="checkbox" id="birthday" name="category[]" value="Dzimšanas diena">
                            <label for="birthday">Dzimšanas diena</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="corporate" name="category[]" value="Korporatīvais">
                            <label for="corporate">Korporatīvais</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="wedding" name="category[]" value="Kāzas">
                            <label for="wedding">Kāzas</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="other" name="category[]" value="Cits">
                            <label for="other">Cits</label>
                        </div>
                    </div>
                </div>

                <!-- Dzimums Checkboxes -->
                <div>
                    <label for="gender">Dzimums:</label>
                    <div class="gender-options">
                        <div class="checkbox-item">
                            <input type="checkbox" id="male" name="gender[]" value="Vīrietis">
                            <label for="male">Vīrietis</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="female" name="gender[]" value="Sieviete">
                            <label for="female">Sieviete</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="both" name="gender[]" value="Abi">
                            <label for="both">Abi</label>
                        </div>
                    </div>
                </div>

                
                <button type="submit">Filtrēt</button>
            </form>
        </div>

        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <?php
                $eventFolder = 'images/event_' . $event['id'] . '/';
                $images = glob($eventFolder . '*');
                $firstImage = $images ? $images[0] : 'placeholder.jpg';
                ?>
                <div class="event-card">
                    <a href="events.php?id=<?php echo htmlspecialchars($event['id']); ?>">
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
