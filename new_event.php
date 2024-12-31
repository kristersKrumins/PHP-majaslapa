<?php
require_once 'Database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $age_min = $_POST['age_min'];
    $age_max = $_POST['age_max'];
    $gender = $_POST['gender'];
    $category = $_POST['category'];

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert event details into the database
        $stmt = $pdo->prepare("INSERT INTO events (NOSAUKUMS, APRAKSTS, CENA, VECUMS, VECUMS2, DZIMUMS) VALUES (:title, :description, :price, :age_min, :age_max, :gender)");
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':price' => $price,
            ':age_min' => $age_min,
            ':age_max' => $age_max,
            ':gender' => $gender
        ]);
        $event_id = $pdo->lastInsertId();

        // Handle multiple file uploads
        if (isset($_FILES['images'])) {
            $eventFolder = 'images/event_' . $event_id . '/';
            if (!is_dir($eventFolder)) {
                mkdir($eventFolder, 0755, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $imageNewName = uniqid('img_', true) . '.jpg';
                    $imageDestination = $eventFolder . $imageNewName;

                    move_uploaded_file($tmp_name, $imageDestination);
                }
            }
        }

        header("Location: index.php?success=1");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izveido jaunu pasākumu</title>
    <link rel="stylesheet" href="css/new_event.css">
</head>
<body>
    <header>
        <h1>Izveido jaunu pasākumu</h1>
    </header>
    <main>
        <?php if (isset($errorMessage)): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form action="new_event.php" method="post" enctype="multipart/form-data">
    <label for="title">Pasākuma nosaukums:</label>
    <input type="text" id="title" name="title" required>

    <label for="description">Pasākuma apraksts:</label>
    <textarea id="description" name="description" rows="4" required></textarea>

    <label for="price">Cena (€):</label>
    <input type="number" id="price" name="price" required>

    <label>Vecums:</label>
    <div class="age-range">
        <input type="number" id="age_min" name="age_min"  required>
        <span class="to-label">LĪDZ</span>
        <input type="number" id="age_max" name="age_max"  required>
    </div>

    <label for="gender">Dzimums:</label>
    <div class="gender-options">
    <input type="radio" id="male" name="gender" value="Vīrietis">
    <label for="male">Vīrietis</label>

    <input type="radio" id="female" name="gender" value="Sieviete">
    <label for="female">Sieviete</label>

    <input type="radio" id="both" name="gender" value="Abi">
    <label for="both">Abi</label>
</div>

<label for="category">Kategorija:</label>
<div class="category-options">
    <input type="radio" id="Maģija" name="category" value="Maģija">
    <label for="Maģija">Maģija</label>

    <input type="radio" id="Princeses" name="category" value="Princeses">
    <label for="Princeses">Princeses</label>

    <input type="radio" id="Kovboji" name="category" value="Kovboji">
    <label for="Kovboji">Kovboji</label>

    <input type="radio" id="Pirāti" name="category" value="Pirāti">
    <label for="Pirāti">Pirāti</label>

    <input type="radio" id="Klauni" name="category" value="Klauni">
    <label for="Klauni">Klauni</label>

    <input type="radio" id="Disko" name="category" value="Disko">
    <label for="Disko">Disko</label>

    <input type="radio" id="Ziemassvētki" name="category" value="Ziemassvētki">
    <label for="Ziemassvētki">Ziemassvētki</label>

    <input type="radio" id="Burbuļi" name="category" value="Burbuļi">
    <label for="Burbuļi">Burbuļi</label>

</div>

    <label for="images">Pievieno bildes:</label>
    <input type="file" id="images" name="images[]" accept="image/*" multiple required>

    <button type="submit">Saglabāt pasākumu</button>
    
</form>

    </main>
</body>
</html>
