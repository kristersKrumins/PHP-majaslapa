<?php
session_start();
require_once 'Database/config.php';

if (!isset($_GET['id'])) {
    die("No event ID provided. <a href='index.php'>Go back</a>");
}

$event_id = (int)$_GET['id'];
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 0;
$username = $logged_in ? $_SESSION['username'] : null;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE ID = :id");
    $stmt->execute([':id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found. <a href='index.php'>Go back</a>");
    }

    $eventFolder = 'images/event_' . $event_id . '/';
    $images = glob($eventFolder . '*');

    // Handle delete event
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $admin) {
        try {
            // Delete event
            $delete_stmt = $pdo->prepare("DELETE FROM events WHERE ID = :id");
            $delete_stmt->execute([':id' => $event_id]);

            // Delete associated images
            foreach ($images as $image) {
                if (file_exists($image)) {
                    unlink($image); // Remove the image file
                }
            }

             // Delete the folder if it exists and is empty
            if (is_dir($eventFolder)) {
                rmdir($eventFolder); // Remove the folder
            }

            // Redirect after deletion
            header("Location: index.php?success=2");
            exit;
        } catch (PDOException $e) {
            die("Error deleting event: " . $e->getMessage());
        }
    }

    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review']) && $logged_in) {
        $review_text = trim($_POST['review']);
        $rating = (float)$_POST['rating'];

        if ($rating > 0 && !empty($review_text)) {
            $review_stmt = $pdo->prepare("
                INSERT INTO ATSAUKSMES (PASAKUMA_ID, LIETOTAJVARDS, REITINGS, ATSAUKSME) 
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

    // Fetch reviews
    $review_stmt = $pdo->prepare("SELECT * FROM ATSAUKSMES WHERE PASAKUMA_ID = :event_id ORDER BY DATUMS DESC");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></title>
    <link rel="stylesheet" href="css/events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Photo slider
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

            showImage(currentIndex);

            // Star rating system
            const stars = document.querySelectorAll('.star-rating .star');
            const ratingInput = document.getElementById('rating-input');

            stars.forEach((star, index) => {
                // Mouseover: Highlight stars up to the hovered one
                star.addEventListener('mouseover', () => {
                    stars.forEach((s, i) => {
                        s.classList.toggle('hovered', i <= index);
                    });
                });

                // Mouseleave: Remove hover highlight
                star.addEventListener('mouseleave', () => {
                    stars.forEach((s) => s.classList.remove('hovered'));
                });

                // Click: Set the rating and update visual selection
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
                    <?php foreach ($images as $index => $image): ?>
                        <span data-index="<?php echo $index; ?>"></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <h2><?php echo htmlspecialchars($event['NOSAUKUMS']); ?></h2>
            <p><?php echo htmlspecialchars($event['APRAKSTS']); ?></p>
            <h2>Cena</h2>
            <p>€<?php echo htmlspecialchars($event['CENA']); ?></p>
            <div class="action-buttons">
                <a href="index.php" class="back-btn">Atpakaļ</a>
                <?php if ($logged_in && (int)$admin === 1): ?>
                    <a href="edit_event.php?id=<?php echo htmlspecialchars($event['id']); ?>" class="edit-btn">Reģidēt</a>
                    <form method="post" class="delete-form">
                        <button type="submit" name="delete" class="delete-btn">Dzēst pasākumu</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews Section -->
        <section class="reviews-section">
            <h3>Atsauksmes</h3>
            <?php if ($logged_in): ?>
                <form method="post" class="review-form">
                    <div class="star-rating">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="fa fa-star star"></span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="rating-input" name="rating" value="0">
                    <textarea name="review" rows="4" placeholder="jūsu atsauksme..." required></textarea>
                    <button type="submit">Pievienot atsauksmi</button>
                </form>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Lūdzu <a href="login.php">pieslēdzieties</a> ,lai rakstītu atsauksmes.</p>
            <?php endif; ?>

            <h3>Pievienotās atsauksmes</h3>
            <?php if (!empty($reviews)): ?>
                <ul class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <li class="review">
                            <p><strong><?php echo htmlspecialchars($review['lietotajvards']); ?></strong> - 
                                <span><?php echo htmlspecialchars($review['datums']); ?></span></p>
                            <p class="submitted-stars">
                                <?php 
                                $fullStars = floor($review['reitings']);
                                $halfStar = ($review['reitings'] - $fullStars) >= 0.5;
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
