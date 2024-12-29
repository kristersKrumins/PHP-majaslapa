<?php
session_start();

$mainFolder = 'images/'; // Path to the main images folder
$folders = glob($mainFolder . 'event_*'); // Dynamically fetch all event folders
$images = [];

// Loop through each folder and collect all image paths
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        $folderImages = glob($folder . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        $images = array_merge($images, $folderImages); // Merge images from all folders
    }
}

if (!$images) {
    $images = []; // Fallback in case there are no images
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie</title>
    <link rel="stylesheet" href="css/galerie.css">
</head>
<body>
    <header>
        <!-- Top Bar -->
        <div class="top-bar">
            <img src="images/logo.png" alt="Website Logo" class="logo">
        </div>
    </div>

        <!-- Logo Bar -->
        <div class="logo-bar">
            <h1>Galerija</h1>
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
        <div class="gallery-container">
            <?php
            foreach ($images as $index => $image) {
                echo '<div class="gallery-item">';
                echo "<img src='$image' alt='Event Image' onclick='openPopup($index)'>";
                echo '</div>';
            }
            ?>
        </div>
    </main>

    <!-- Popup Overlay -->
    <div class="popup-overlay" id="popup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <span class="nav-btn left" onclick="navigate(-1)">&#10094;</span>
        <img id="popup-img" src="" alt="Popup Image">
        <span class="nav-btn right" onclick="navigate(1)">&#10095;</span>
        <div class="photo-number" id="photo-number"></div>
    </div>

    <script>
        const images = <?php echo json_encode($images); ?>; // Pass PHP array to JavaScript
        let currentIndex = 0;

        // Open Popup
        function openPopup(index) {
            const popup = document.getElementById('popup');
            const popupImg = document.getElementById('popup-img');
            const photoNumber = document.getElementById('photo-number');
            currentIndex = index;
            popupImg.src = images[currentIndex];
            photoNumber.textContent = `${currentIndex + 1} / ${images.length}`; // Set photo number
            popup.style.display = 'flex'; // Show the popup
        }

        // Close Popup
        function closePopup() {
            const popup = document.getElementById('popup');
            popup.style.display = 'none'; // Hide the popup
        }

        // Navigate Images
        function navigate(direction) {
            const popupImg = document.getElementById('popup-img');
            const photoNumber = document.getElementById('photo-number');
            currentIndex = (currentIndex + direction + images.length) % images.length; // Wrap around
            popupImg.src = images[currentIndex];
            photoNumber.textContent = `${currentIndex + 1} / ${images.length}`; // Update photo number
        }

        // Close Popup when clicking outside the image
        document.getElementById('popup').addEventListener('click', (event) => {
            if (event.target.id === 'popup') {
                closePopup();
            }
        });
    </script>
</body>
</html>
