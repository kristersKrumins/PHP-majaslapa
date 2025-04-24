<?php
session_start();

// Ceļš uz galveno attēlu mapi
$mainFolder = 'images/'; 
// Dinamiski iegūt visas pasākumu mapes
$folders = glob($mainFolder . 'event_*'); 
$images = [];

// Ciklam iziet cauri katrai mapei un savākt visus attēlu ceļus
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        $folderImages = glob($folder . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        // Apvienot attēlus no visām mapēm
        $images = array_merge($images, $folderImages); 
    }
}

// Rezerves variants gadījumā, ja nav neviena attēla
if (!$images) {
    $images = []; 
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
        <!-- Augšējā josla -->
        <div class="top-bar">
            <img src="images/logo.png" alt="Vietnes logo" class="logo">
        </div>
    </div>

        <!-- Logo josla -->
        <div class="logo-bar">
            <h1>Galerija</h1>
        </div>

        <!-- Navigācijas josla -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Sākumlapa</a></li>
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
                echo "<img src='$image' alt='Pasākuma attēls' onclick='openPopup($index)'>";
                echo '</div>';
            }
            ?>
        </div>
    </main>

    <!-- Uznirstošais pārklājums (Popup Overlay) -->
    <div class="popup-overlay" id="popup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <span class="nav-btn left" onclick="navigate(-1)">&#10094;</span>
        <img id="popup-img" src="" alt="Uznirstošais attēls">
        <span class="nav-btn right" onclick="navigate(1)">&#10095;</span>
        <div class="photo-number" id="photo-number"></div>
    </div>

    <script>
        // Pārsūtīt PHP masīvu uz JavaScript
        const images = <?php echo json_encode($images); ?>; 
        let currentIndex = 0;

        // Atvērt uznirstošo logu
        function openPopup(index) {
            const popup = document.getElementById('popup');
            const popupImg = document.getElementById('popup-img');
            const photoNumber = document.getElementById('photo-number');
            currentIndex = index;
            popupImg.src = images[currentIndex];
            photoNumber.textContent = `${currentIndex + 1} / ${images.length}`; // Iestatīt foto numuru
            popup.style.display = 'flex'; // Parādīt uznirstošo logu
        }

        // Aizvērt uznirstošo logu
        function closePopup() {
            const popup = document.getElementById('popup');
            popup.style.display = 'none'; // Paslēpt uznirstošo logu
        }

        // Navigēt pa attēliem
        function navigate(direction) {
            const popupImg = document.getElementById('popup-img');
            const photoNumber = document.getElementById('photo-number');
            // Apgriezienā, ja sasniedzam sākumu vai beigas
            currentIndex = (currentIndex + direction + images.length) % images.length; 
            popupImg.src = images[currentIndex];
            photoNumber.textContent = `${currentIndex + 1} / ${images.length}`; // Atjaunināt foto numuru
        }

        // Aizvērt uznirstošo logu, ja noklikšķina ārpus attēla
        document.getElementById('popup').addEventListener('click', (event) => {
            if (event.target.id === 'popup') {
                closePopup();
            }
        });
    </script>
</body>
</html>
