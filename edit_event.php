<?php
session_start();
require_once 'Database/config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['admin'] != 1) {
    die("Access denied. <a href='index.php'>Go back</a>");
}

// Check if the event ID is provided
if (!isset($_GET['id'])) {
    die("No event ID provided. <a href='index.php'>Go back</a>");
}

$event_id = (int)$_GET['id'];

try {
    // Database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE ID = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found. <a href='index.php'>Go back</a>");
    }

    $eventFolder = 'images/event_' . $event_id . '/'; // Use event-specific folder
    if (!is_dir($eventFolder)) {
        mkdir($eventFolder, 0777, true); // Create folder if it doesn't exist
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission to update event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    // Check if a new image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageNewName = uniqid('event_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageDestination = $eventFolder . $imageNewName;

        // Ensure the upload folder exists and is writable
        if (!is_dir($eventFolder) || !is_writable($eventFolder)) {
            die("Upload folder does not exist or is not writable.");
        }

        // Move the uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $imageDestination)) {
            // Delete the old image if it exists
            $oldImage = $eventFolder . $event['BILDE'];
            if (file_exists($oldImage)) {
                unlink($oldImage);
            }

            // Update the image name in the database
            $event['BILDE'] = $imageNewName;
        } else {
            die("Failed to move uploaded file. Check folder permissions and paths.");
        }
    } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle file upload errors
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = "The uploaded file exceeds the allowed size.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = "The file was only partially uploaded.";
                break;
            default:
                $errorMessage = "An unknown error occurred during file upload.";
        }
    }

    // Update event details in the database
    try {
        $updateStmt = $pdo->prepare("UPDATE events SET NOSAUKUMS = :title, APRAKSTS = :description, CENA = :price, BILDE = :image WHERE ID = :id");
        $updateStmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':price' => $price,
            ':image' => $event['BILDE'], // Use the updated image name
            ':id' => $event_id
        ]);

        header("Location: events.php?id=$event_id&success=1");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Error updating event: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rediģē pasākum</title>
    <link rel="stylesheet" href="css/edit_event.css">
</head>
<body>
    <header>
        <h1>Rediģē pasākum</h1>
    </header>
    <main>
        <?php if (isset($errorMessage)): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form action="edit_event.php?id=<?php echo htmlspecialchars($event_id); ?>" method="post" enctype="multipart/form-data">
            <label for="title">Pasākuma nosaukums:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['NOSAUKUMS']); ?>" required>

            <label for="description">Pasākuma apraksts:</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['APRAKSTS']); ?></textarea>

            <label for="price">Cena (€):</label>
            <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($event['CENA']); ?>" required>

            <label for="image">Pievieno jaunas bildes (neobligāti):</label>
            <input type="file" id="image" name="image" accept="image/*">

            <button type="submit">Saglabāt izmaiņas</button>
        </form>

        <a href="events.php?id=<?php echo htmlspecialchars($event_id); ?>" class="back-btn">Atpakaļ</a>
    </main>
</body>
</html>
