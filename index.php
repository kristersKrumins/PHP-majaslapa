<?php
session_start();
require_once 'Database/config.php';

// Check session
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username  = $logged_in ? $_SESSION['username'] : null;
$admin     = isset($_SESSION['admin']) ? (int)$_SESSION['admin'] : 0;
$user_id   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_unset();
    header("Location: index.php");
    exit;
}

// DB connect
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}

/************************************************************
 * (1) ADMIN ACCEPT/REJECT => sets status
 *     Optionally reset seen_by_user=0 so user sees new status.
 ************************************************************/
if ($logged_in && $admin === 1 && isset($_GET['notif_id']) && isset($_GET['action'])) {
    $notif_id = (int)$_GET['notif_id'];
    $action   = $_GET['action']; // 'accept' or 'reject'

    if ($action === 'accept') {
        $stm = $pdo->prepare("
            UPDATE notifications
               SET status        = 'accepted',
                   seen_by_admin = 1,
                   seen_by_user  = 0
             WHERE id            = :id
        ");
        $stm->execute([':id' => $notif_id]);
    } elseif ($action === 'reject') {
        $stm = $pdo->prepare("
            UPDATE notifications
               SET status        = 'rejected',
                   seen_by_admin = 1,
                   seen_by_user  = 0
             WHERE id            = :id
        ");
        $stm->execute([':id' => $notif_id]);
    }
    header("Location: index.php");
    exit;
}

/************************************************************
 * (2) FETCH NOTIFICATIONS
 ************************************************************/
$notifications = [];
$unseenCount   = 0;

if ($logged_in) {
    try {
        if ($admin === 1) {
            // Admin sees all
            $sql = "
                SELECT n.*,
                       u.username   AS requestor_name,
                       e.NOSAUKUMS  AS event_name
                  FROM notifications n
             LEFT JOIN users   u ON n.user_id  = u.id
             LEFT JOIN events  e ON n.event_id = e.id
              ORDER BY n.created_at DESC
            ";
            $stm = $pdo->prepare($sql);
            $stm->execute();
            $notifications = $stm->fetchAll(PDO::FETCH_ASSOC);

            // Count how many are unread for admin => (if you have separate columns)
            foreach ($notifications as $nt) {
                if ($nt['seen_by_admin'] == 0) {
                    $unseenCount++;
                }
            }
        } else {
            // Regular user => only own notifications
            $sql = "
                SELECT n.*,
                       u.username   AS requestor_name,
                       e.NOSAUKUMS  AS event_name
                  FROM notifications n
             LEFT JOIN users  u ON n.user_id  = u.id
             LEFT JOIN events e ON n.event_id = e.id
                 WHERE n.user_id = :uid
              ORDER BY n.created_at DESC
            ";
            $stm = $pdo->prepare($sql);
            $stm->execute([':uid' => $user_id]);
            $notifications = $stm->fetchAll(PDO::FETCH_ASSOC);

            // Count how many are unread for user => (if you have separate columns)
            foreach ($notifications as $nt) {
                if ($nt['seen_by_user'] == 0) {
                    $unseenCount++;
                }
            }
        }
    } catch (PDOException $e) {
        die("Notifications fetch error: " . $e->getMessage());
    }
}

/************************************************************
 * (3) FETCH EVENTS + FILTERS
 ************************************************************/
$query  = "SELECT * FROM events WHERE 1=1";
$params = [];

// -------------- Name filter --------------
if (!empty($_GET['name'])) {
    $query .= " AND NOSAUKUMS LIKE :n";
    $params[':n'] = '%'.$_GET['name'].'%';
}

// -------------- Max Price --------------
if (!empty($_GET['price'])) {
    $query .= " AND CENA <= :p";
    $params[':p'] = (float)$_GET['price'];
}

// -------------- Age Filter --------------
$minAge = !empty($_GET['min_age']) ? (int)$_GET['min_age'] : 0;
$maxAge = !empty($_GET['max_age']) ? (int)$_GET['max_age'] : 0;

/**
 * The logic is:
 *  if both minAge and maxAge are > 0, we want events whose [VECUMS..VECUMS2] fully includes [minAge..maxAge].
 *    => event.VECUMS <= :minAge
 *       AND event.VECUMS2 >= :maxAge
 *
 *  if only minAge => we ensure minAge is in [VECUMS..VECUMS2].
 *    => event.VECUMS <= :minAge <= event.VECUMS2
 *
 *  if only maxAge => we ensure maxAge is in [VECUMS..VECUMS2].
 *    => event.VECUMS <= :maxAge <= event.VECUMS2
 */
if ($minAge > 0 && $maxAge > 0) {
    // user provided both => event range must include [minAge..maxAge]
    $query .= " AND VECUMS <= :minAge AND VECUMS2 >= :maxAge";
    $params[':minAge'] = $minAge;
    $params[':maxAge'] = $maxAge;

} elseif ($minAge > 0 && $maxAge == 0) {
    // only minAge => minAge is in [VECUMS..VECUMS2]
    $query .= " AND :minAge BETWEEN VECUMS AND VECUMS2";
    $params[':minAge'] = $minAge;

} elseif ($maxAge > 0 && $minAge == 0) {
    // only maxAge => maxAge is in [VECUMS..VECUMS2]
    $query .= " AND :maxAge BETWEEN VECUMS AND VECUMS2";
    $params[':maxAge'] = $maxAge;
}

// -------------- Gender checkboxes --------------
if (!empty($_GET['gender']) && is_array($_GET['gender'])) {
    $phArr = [];
    foreach ($_GET['gender'] as $i => $g) {
        $ph = ":gender$i";
        $phArr[] = $ph;
        $params[$ph] = $g;
    }
    if (count($phArr) > 0) {
        $in = implode(',', $phArr);
        $query .= " AND DZIMUMS IN ($in)";
    }
}

// -------------- Category checkboxes --------------
if (!empty($_GET['category']) && is_array($_GET['category'])) {
    $catArr = [];
    foreach ($_GET['category'] as $i => $cat) {
        $ph = ":cat$i";
        $catArr[] = $ph;
        $params[$ph] = $cat;
    }
    if (count($catArr) > 0) {
        $in = implode(',', $catArr);
        $query .= " AND KATEGORIJA IN ($in)";
    }
}

// Exec the final query
try {
    $stm2 = $pdo->prepare($query);
    $stm2->execute($params);
    $events = $stm2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Events fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Children's Event Hosting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Success banner fade-out
        const sb = document.getElementById('success-banner');
        if (sb) {
            setTimeout(() => {
                sb.style.opacity = '0';
                setTimeout(() => {
                    sb.style.display = 'none';
                    const url = new URL(window.location.href);
                    url.searchParams.delete('success');
                    window.history.replaceState(null, '', url);
                }, 1000);
            }, 5000);
        }
    });
    </script>
</head>
<body>
    <header>
        <div class="top-bar">
            <img src="images/logo.png" alt="Logo" class="logo">

            <?php if ($logged_in): ?>
                <!-- USER INFO RIGHT -->
                <div class="user-info">
                    <div class="user-info-top-row">
                        <!-- NOTIFICATION BELL -->
                        <div class="notification-container">
                            <button id="notification-btn" class="notification-btn">
                                <span class="bell-icon">&#128276;</span>
                                <?php if ($unseenCount > 0): ?>
                                    <span class="badge"><?php echo $unseenCount; ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notification-dropdown" class="notification-dropdown">
                                <h4>Notifications</h4>
                                <hr>
                                <?php if (empty($notifications)): ?>
                                    <p>No notifications</p>
                                <?php else: ?>
                                    <?php foreach ($notifications as $nt): ?>
                                        <?php
                                        $eventName = $nt['event_name'] ?? 'unknown event';
                                        $userName  = $nt['requestor_name'] ?? 'unknown user';
                                        $status    = $nt['status'];
                                        $nid       = $nt['id'];

                                        // Translate the status to Latvian
                                        if ($status === 'pending') {
                                            $lvStatus = 'Statuss: Apstrādā';
                                        } elseif ($status === 'accepted') {
                                            $lvStatus = 'Statuss: Apstiprināts';
                                        } else {
                                            $lvStatus = 'Statuss: Noliegts';
                                        }
                                        ?>
                                        <div class="notif-item">
                                            <?php
                                            // Admin vs user text
                                            if ($admin === 1) {
                                                if ($status === 'pending') {
                                                    echo "<p><strong>Lietotājs $userName</strong> vēlas pieteikties uz <strong>$eventName</strong></p>";
                                                } elseif ($status === 'accepted') {
                                                    echo "<p><strong>$userName</strong> ir apstiprināts pasākumam <strong>$eventName</strong></p>";
                                                } else {
                                                    echo "<p><strong>$userName</strong> ir noraidīts pasākumam <strong>$eventName</strong></p>";
                                                }
                                            } else {
                                                // user sees simpler text
                                                if ($status === 'pending') {
                                                    echo "<p>Jūsu pieprasījums pievienoties <strong>$eventName</strong> ir apstrādē.</p>";
                                                } elseif ($status === 'accepted') {
                                                    echo "<p>Jūsu pieprasījums pievienoties <strong>$eventName</strong> ir apstiprināts!</p>";
                                                } else {
                                                    echo "<p>Jūsu pieprasījums pievienoties <strong>$eventName</strong> ir noraidīts.</p>";
                                                }
                                            }
                                            ?>
                                            <!-- Print the Latvian status line -->
                                            <small><?php echo htmlspecialchars($lvStatus); ?></small>

                                            <?php if ($admin === 1 && $status === 'pending'): ?>
                                                <div class="admin-actions">
                                                    <a href="?notif_id=<?php echo $nid; ?>&action=accept" class="accept-btn">Accept</a>
                                                    <a href="?notif_id=<?php echo $nid; ?>&action=reject" class="reject-btn">Reject</a>
                                                </div>
                                            <?php endif; ?>
                                            <hr>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="index.php?logout=1" class="logout-btn">Logout</a>
                    </div>
                    <span>User: <?php echo htmlspecialchars($username); ?></span>
                </div>
            <?php else: ?>
                <div class="user-info" style="margin-left:auto;">
                    <a href="login.php" class="login-btn">Login</a>
                </div>
            <?php endif; ?>
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

    <main class="content-wrapper">
        <div class="main-content">
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div id="success-banner" class="success-message">
                    Pasākums izveidots veiksmīgi!
                </div>
            <?php elseif (isset($_GET['success']) && $_GET['success'] == 2): ?>
                <div id="success-banner" class="success-message">
                    Pasākums izdzēsts veiksmīgi!
                </div>
            <?php endif; ?>

            <div class="main-heading">
                <?php if ($logged_in && $admin === 1): ?>
                    <a href="new_event.php" class="new-event-btn">Izveidot jaunu pasākumu</a>
                <?php endif; ?>
            </div>

            <!-- Event Grid -->
            <div class="event-grid">
                <?php foreach ($events as $ev): ?>
                    <?php
                    $eventFolder = 'images/event_'.$ev['id'].'/';
                    $imgs = glob($eventFolder.'*');
                    $firstImg = $imgs ? $imgs[0] : 'placeholder.jpg';
                    ?>
                    <div class="event-card">
                        <a href="events.php?id=<?php echo htmlspecialchars($ev['id']); ?>">
                            <img src="<?php echo $firstImg; ?>" alt="<?php echo htmlspecialchars($ev['NOSAUKUMS']); ?>">
                            <h3><?php echo htmlspecialchars($ev['NOSAUKUMS']); ?></h3>
                        </a>
                        <p><?php echo htmlspecialchars($ev['APRAKSTS']); ?></p>
                        <span class="price">€<?php echo htmlspecialchars($ev['CENA']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SIDEBAR Filter -->
        <div id="sidebar">
            <div class="filter-form">
                <form method="GET" action="index.php">
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
                        <div class="age-inputs">
                            <label for="min-age">Min:</label>
                            <input type="number" id="min-age" name="min_age" placeholder="Min Age">
                            <label for="max-age">Max:</label>
                            <input type="number" id="max-age" name="max_age" placeholder="Max Age">
                        </div>
                    </div>

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
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Children's Event Hosting</p>
    </footer>
</body>
</html>
