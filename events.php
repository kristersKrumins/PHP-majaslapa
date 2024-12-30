<?php
session_start();
require_once 'Database/config.php';

if (!isset($_GET['id'])) {
    die("No event ID provided. <a href='index.php'>Go back</a>");
}

$event_id = (int)$_GET['id'];
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$admin = isset($_SESSION['admin']) ? (int)$_SESSION['admin'] : 0;
$username = $logged_in ? $_SESSION['username'] : null;
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

try {
    // Connect to DB
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE ID = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found. <a href='index.php'>Go back</a>");
    }

    // 2) Gather event images
    $eventFolder = 'images/event_' . $event_id . '/';
    $images = glob($eventFolder . '*');

    // 3) If admin posted 'Delete Event'
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $admin) {
        try {
            // Delete the event row
            $delete_stmt = $pdo->prepare("DELETE FROM events WHERE ID = :id");
            $delete_stmt->execute([':id' => $event_id]);

            // Delete associated images
            foreach ($images as $image) {
                if (file_exists($image)) {
                    unlink($image);
                }
            }
            // Remove the folder if empty
            if (is_dir($eventFolder)) {
                @rmdir($eventFolder);
            }

            // Redirect after deletion
            header("Location: index.php?success=2");
            exit;
        } catch (PDOException $e) {
            die("Error deleting event: " . $e->getMessage());
        }
    }

    // 4) Handle "Pieteikties" (join) request for a non-admin user
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['join_event'])
        && $logged_in
        && !$admin
    ) {
        // Check if this user already has a pending request for this event
        $check = $pdo->prepare("
            SELECT *
              FROM notifications
             WHERE user_id = :uid
               AND event_id= :eid
               AND status = 'pending'
        ");
        $check->execute([
            ':uid' => $user_id,
            ':eid' => $event_id
        ]);
        $existingPending = $check->fetch();

        // If no existing pending => create new row
        if (!$existingPending) {
            $message = "Lietotājs '$username' vēlas pieteikties uz pasākumu: {$event['NOSAUKUMS']}";
            // Insert with seen_by_user=0, seen_by_admin=0
            $ins = $pdo->prepare("
                INSERT INTO notifications (
                    user_id, event_id, message, status,
                    seen_by_user, seen_by_admin,
                    created_at
                )
                VALUES (
                    :uid, :eid, :msg, 'pending',
                    0, 0,
                    NOW()
                )
            ");
            $ins->execute([
                ':uid' => $user_id,
                ':eid' => $event_id,
                ':msg' => $message
            ]);
        }
        // Redirect back
        header("Location: events.php?id=$event_id");
        exit;
    }

    // 5) For a logged-in non-admin, check if they have any request for this event
    $existingRequest = null;
    if ($logged_in && !$admin) {
        $req_stmt = $pdo->prepare("
            SELECT *
              FROM notifications
             WHERE user_id = :uid
               AND event_id= :eid
             ORDER BY created_at DESC
             LIMIT 1
        ");
        $req_stmt->execute([
            ':uid' => $user_id,
            ':eid' => $event_id
        ]);
        $existingRequest = $req_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 6) Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['review'])
        && $logged_in
        && !isset($_POST['delete'])
        && !isset($_POST['join_event'])
    ) {
        $review_text = trim($_POST['review']);
        $rating = (float)$_POST['rating'];

        if ($rating > 0 && !empty($review_text)) {
            $review_stmt = $pdo->prepare("
                INSERT INTO ATSAUKSMES (
                    PASAKUMA_ID, LIETOTAJVARDS, REITINGS, ATSAUKSME
                )
                VALUES (:event_id, :username, :rating, :comment)
            ");
            $review_stmt->execute([
                ':event_id' => $event_id,
                ':username' => $username,
                ':rating' => $rating,
                ':comment' => $review_text
            ]);
            header("Location: events.php?id=$event_id");
            exit;
        } else {
            $error = "Please provide a valid rating and comment.";
        }
    }

    // 7) Fetch reviews
    $review_stmt = $pdo->prepare("
        SELECT *
          FROM ATSAUKSMES
         WHERE PASAKUMA_ID = :event_id
         ORDER BY DATUMS DESC
    ");
    $review_stmt->execute([':event_id' => $event_id]);
    $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/events.css">
    <!-- Optional: Font Awesome for stars -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Photo Slider
        const images = document.querySelectorAll('.image-slider img');
        const dots = document.querySelectorAll('.slider-dots span');
        const arrowLeft = document.querySelector('.arrow-left');
        const arrowRight = document.querySelector('.arrow-right');
        let currentIndex = 0;

        function showImage(index) {
            images.forEach((img, i) => {
                img.classList.toggle('active', i === index);
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }

        arrowLeft.addEventListener('click', function () {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            showImage(currentIndex);
        });

        arrowRight.addEventListener('click', function () {
            currentIndex = (currentIndex + 1) % images.length;
            showImage(currentIndex);
        });

        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                currentIndex = i;
                showImage(currentIndex);
            });
        });

        showImage(currentIndex);

        // Star rating
        const stars = document.querySelectorAll('.star-rating .star');
        const ratingInput = document.getElementById('rating-input');

        stars.forEach((star, index) => {
            // Hover
            star.addEventListener('mouseover', () => {
                stars.forEach((s, i) => {
                    s.classList.toggle('hovered', i <= index);
                });
            });
            // Mouseleave
            star.addEventListener('mouseleave', () => {
                stars.forEach((s) => s.classList.remove('hovered'));
            });
            // Click => set rating
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;
                stars.forEach((s, i) => {
                    s.classList.toggle('selected', i < rating);
                });
            });
        });
    });
    </script>
</head>
<body>
    <header></header>
    <main>
        <!-- Event Details -->
        <div class="event-details">
            <!-- Image Slider -->
            <div class="image-slider">
                <span class="arrow-left">❮</span>
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo $image; ?>" alt="Event Image">
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No images available for this event.</p>
                <?php endif; ?>
                <span class="arrow-right">❯</span>
            </div>
            <div class="slider-dots">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $index => $img): ?>
                        <span data-index="<?php echo $index; ?>"></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h1><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></h1>
            <p><?php echo htmlspecialchars($event['APRAKSTS']); ?></p>

            <h2>Cena</h2>
            <p>€<?php echo htmlspecialchars($event['CENA']); ?></p>

            <h2>Vecuma diapazons</h2>
            <p>No <?php echo htmlspecialchars($event['VECUMS']); ?>
               līdz <?php echo htmlspecialchars($event['VECUMS2']); ?> gadiem
            </p>

            <h2>Dzimums</h2>
            <p><?php echo htmlspecialchars($event['DZIMUMS']); ?></p>

            <h2>Kategorija</h2>
            <p><?php echo htmlspecialchars($event['KATEGORIJA']); ?></p>

            <div class="action-buttons">
                <!-- Back button -->
                <a href="index.php" class="back-btn">Atpakaļ</a>

                <?php if ($logged_in && $admin === 1): ?>
                    <!-- Edit event -->
                    <a href="edit_event.php?id=<?php echo htmlspecialchars($event['id']); ?>" class="edit-btn">Rediģēt</a>
                    <!-- Delete event -->
                    <form method="post" class="delete-form">
                        <button type="submit" name="delete" class="delete-btn">Dzēst pasākumu</button>
                    </form>
                <?php else: ?>
                    <!-- Non-admin "Join" button logic -->
                    <?php if ($logged_in && !$admin): ?>
                        <?php
                        // If user has a request for this event
                        if ($existingRequest) {
                            // Show Latvian statuses for the user
                            if ($existingRequest['status'] === 'pending') {
                                // "Gaida atbildi (Apstrādā)"
                                echo '<button class="join-btn disabled" disabled>Gaida atbildi (Apstrādā)</button>';
                            } elseif ($existingRequest['status'] === 'accepted') {
                                // "Apstiprināts"
                                echo '<button class="join-btn accepted" disabled>Apstiprināts</button>';
                            } elseif ($existingRequest['status'] === 'rejected') {
                                // "Noliegts"
                                echo '<button class="join-btn rejected" disabled>Noliegts</button>';
                            }
                        } else {
                            // No request => show "Pieteikties"
                            echo '
                            <form method="post" style="display:inline;">
                                <button type="submit" name="join_event" class="join-btn">
                                    Pieteikties
                                </button>
                            </form>
                            ';
                        }
                        ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews Section -->
        <section class="reviews-section">
            <h3>Atsauksmes</h3>
            <?php if ($logged_in): ?>
                <!-- Review Form -->
                <form method="post" class="review-form">
                    <div class="star-rating">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="fa fa-star star"></span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="rating-input" name="rating" value="0">
                    <textarea name="review" rows="4" placeholder="Jūsu atsauksme..." required></textarea>
                    <button type="submit">Pievienot atsauksmi</button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Lūdzu <a href="login.php">pieslēdzieties</a>, lai rakstītu atsauksmes.</p>
            <?php endif; ?>

            <h3>Pievienotās atsauksmes</h3>
            <?php if (!empty($reviews)): ?>
                <ul class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <li class="review">
                            <p>
                                <strong><?php echo htmlspecialchars($review['lietotajvards']); ?></strong> 
                                - <span><?php echo htmlspecialchars($review['datums']); ?></span>
                            </p>
                            <p class="submitted-stars">
                                <?php
                                $fullStars = floor($review['reitings']);
                                $halfStar  = ($review['reitings'] - $fullStars) >= 0.5;
                                for ($i = 0; $i < 5; $i++) {
                                    if ($i < $fullStars) {
                                        echo '<span class="fa fa-star star view-only selected"></span>';
                                    } elseif ($halfStar && $i === $fullStars) {
                                        echo '<span class="fa fa-star-half-o star view-only selected"></span>';
                                    } else {
                                        echo '<span class="fa fa-star star view-only"></span>';
                                    }
                                }
                                ?>
                            </p>
                            <p><?php echo htmlspecialchars($review['atsauksme']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Vēl nav nevienas atsauksmes.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
