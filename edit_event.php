<?php
session_start();
require_once 'Database/config.php';

// Enable error reporting for debugging (remove in production if you wish)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1) Check admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['admin'] != 1) {
    die("Access denied. <a href='index.php'>Go back</a>");
}

// 2) Check event ID
if (!isset($_GET['id'])) {
    die("No event ID provided. <a href='index.php'>Go back</a>");
}
$event_id = (int)$_GET['id'];

try {
    // 3) DB connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4) Fetch event
    $stmt = $pdo->prepare("SELECT * FROM events WHERE ID = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found. <a href='index.php'>Go back</a>");
    }

    // Ensure folder for images
    $eventFolder = 'images/event_' . $event_id . '/';
    if (!is_dir($eventFolder)) {
        mkdir($eventFolder, 0777, true);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 5) Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $price       = (float)$_POST['price'];
    $age_min     = (int)$_POST['age_min'];
    $age_max     = (int)$_POST['age_max'];
    $gender      = $_POST['gender'];
    $category    = $_POST['category'];

    // Optional new image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageNewName = uniqid('event_', true) . '.' . $ext;
        $imageDestination = $eventFolder . $imageNewName;

        // Move the uploaded file
        if (!is_writable($eventFolder)) {
            $errorMessage = "Upload folder is not writable.";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imageDestination)) {
                // Remove old image if it exists
                if (!empty($event['BILDE'])) {
                    $oldImage = $eventFolder . $event['BILDE'];
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                // Update in $event array for usage
                $event['BILDE'] = $imageNewName;
            } else {
                $errorMessage = "Failed to move uploaded file. Check folder permissions.";
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Some file error
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = "The uploaded file exceeds the allowed size.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = "The file was only partially uploaded.";
                break;
            default:
                $errorMessage = "Unknown error uploading file.";
        }
    }

    // 6) Update database if no error
    if (!isset($errorMessage)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE events
                   SET NOSAUKUMS = :title,
                       APRAKSTS = :description,
                       CENA     = :price,
                       BILDE    = :image,
                       VECUMS   = :age_min,
                       VECUMS2  = :age_max,
                       DZIMUMS  = :gender,
                       KATEGORIJA = :category
                 WHERE ID       = :id
            ");
            $updateStmt->execute([
                ':title'       => $title,
                ':description' => $description,
                ':price'       => $price,
                ':image'       => $event['BILDE'], // newly updated image name if any
                ':age_min'     => $age_min,
                ':age_max'     => $age_max,
                ':gender'      => $gender,
                ':category'    => $category,
                ':id'          => $event_id
            ]);

            // 7) Redirect to event page
            header("Location: events.php?id=$event_id&success=1");
            exit;

        } catch (PDOException $e) {
            $errorMessage = "Error updating event: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rediģē pasākumu</title>
    <link rel="stylesheet" href="css/edit_event.css">
</head>
<body>
    <header>
        <h1>Rediģē pasākumu</h1>
    </header>
    <main>
        <?php if (isset($errorMessage)): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form action="edit_event.php?id=<?php echo htmlspecialchars($event_id); ?>" method="post" enctype="multipart/form-data">
            <label for="title">Pasākuma nosaukums:</label>
            <input type="text" id="title" name="title"
                   value="<?php echo htmlspecialchars($event['NOSAUKUMS']); ?>" required>

            <label for="description">Pasākuma apraksts:</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($event['APRAKSTS']); ?></textarea>

            <label for="price">Cena (€):</label>
            <input type="number" id="price" name="price"
                   value="<?php echo htmlspecialchars($event['CENA']); ?>" required>

            <label>Vecums:</label>
            <div class="age-range">
                <input type="number" id="age_min" name="age_min" required
                       value="<?php echo htmlspecialchars($event['VECUMS']); ?>">
                <span class="to-label">LĪDZ</span>
                <input type="number" id="age_max" name="age_max" required
                       value="<?php echo htmlspecialchars($event['VECUMS2']); ?>">
            </div>

            <label for="gender">Dzimums:</label>
            <div class="gender-options">
                <input type="radio" id="male" name="gender" value="Vīrietis"
                       <?php if ($event['DZIMUMS'] === 'Vīrietis') echo 'checked'; ?>>
                <label for="male">Vīrietis</label>

                <input type="radio" id="female" name="gender" value="Sieviete"
                       <?php if ($event['DZIMUMS'] === 'Sieviete') echo 'checked'; ?>>
                <label for="female">Sieviete</label>

                <input type="radio" id="both" name="gender" value="Abi"
                       <?php if ($event['DZIMUMS'] === 'Abi') echo 'checked'; ?>>
                <label for="both">Abi</label>
            </div>

            <label for="category">Kategorija:</label>
            <div class="category-options">
                <!-- Check each radio if it matches existing category -->
                <input type="radio" id="Maģija" name="category" value="Maģija"
                       <?php if ($event['KATEGORIJA'] === 'Maģija') echo 'checked'; ?>>
                <label for="Maģija">Maģija</label>

                <input type="radio" id="Princeses" name="category" value="Princeses"
                       <?php if ($event['KATEGORIJA'] === 'Princeses') echo 'checked'; ?>>
                <label for="Princeses">Princeses</label>

                <input type="radio" id="Kovboji" name="category" value="Kovboji"
                       <?php if ($event['KATEGORIJA'] === 'Kovboji') echo 'checked'; ?>>
                <label for="Kovboji">Kovboji</label>

                <input type="radio" id="pirāti" name="category" value="pirāti"
                       <?php if ($event['KATEGORIJA'] === 'pirāti') echo 'checked'; ?>>
                <label for="pirāti">pirāti</label>

                <input type="radio" id="Klauni" name="category" value="Klauni"
                       <?php if ($event['KATEGORIJA'] === 'Klauni') echo 'checked'; ?>>
                <label for="Klauni">Klauni</label>

                <input type="radio" id="Disko" name="category" value="Disko"
                       <?php if ($event['KATEGORIJA'] === 'Disko') echo 'checked'; ?>>
                <label for="Disko">Disko</label>

                <input type="radio" id="Ziemassvētki" name="category" value="Ziemassvētki"
                       <?php if ($event['KATEGORIJA'] === 'Ziemassvētki') echo 'checked'; ?>>
                <label for="Ziemassvētki">Ziemassvētki</label>

                <input type="radio" id="Burbuļi" name="category" value="Burbuļi"
                       <?php if ($event['KATEGORIJA'] === 'Burbuļi') echo 'checked'; ?>>
                <label for="Burbuļi">Burbuļi</label>
            </div>

            <label for="image">Pievieno jaunu bildi (neobligāti):</label>
            <input type="file" id="image" name="image" accept="image/*">

            <button type="submit">Saglabāt izmaiņas</button>
        </form>

        <a href="events.php?id=<?php echo htmlspecialchars($event_id); ?>" class="back-btn">Atpakaļ</a>
    </main>
</body>
</html>
