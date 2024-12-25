<?php
require_once 'Database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert event details into the database
        $stmt = $pdo->prepare("INSERT INTO events (NOSAUKUMS, APRAKSTS, CENA) VALUES (:title, :description, :price)");
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':price' => $price
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

            <label for="images">Pievieno bildes:</label>
            <input type="file" id="images" name="images[]" accept="image/*" multiple required>

            <button type="submit">Saglabāt pasākumu</button>
        </form>
    </main>
</body>
</html>
