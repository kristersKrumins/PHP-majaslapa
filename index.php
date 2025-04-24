<?php
session_start();
require_once 'Database/config.php';

// Pārbaudīt sesiju
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username  = $logged_in ? $_SESSION['username'] : null;
$admin     = isset($_SESSION['admin']) ? (int)$_SESSION['admin'] : 0;
$user_id   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Apstrādāt izrakstīšanos
if (isset($_GET['logout'])) {
    session_destroy();
    session_unset();
    header("Location: index.php");
    exit;
}

// Savienojums ar DB
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB kļūda: " . $e->getMessage());
}

/************************************************************
 * (1) ADMIN ACCEPT/REJECT => iestata statusu
 ************************************************************/
if ($logged_in && $admin === 1 && isset($_GET['notif_id']) && isset($_GET['action'])) {
    $notif_id = (int)$_GET['notif_id'];
    $action   = $_GET['action']; // 'accept' vai 'reject'

    if ($action === 'accept') {
        $stm = $pdo->prepare("
            UPDATE notifications
               SET status        = 'accepted',
                   seen_by_admin = 1,
                   seen_by_user  = 0
             WHERE id = :id
        ");
        $stm->execute([':id' => $notif_id]);
    } elseif ($action === 'reject') {
        $stm = $pdo->prepare("
            UPDATE notifications
               SET status        = 'rejected',
                   seen_by_admin = 1,
                   seen_by_user  = 0
             WHERE id = :id
        ");
        $stm->execute([':id' => $notif_id]);
    }
    header("Location: index.php");
    exit;
}

/************************************************************
 * (2) IEGŪT PAZIŅOJUMUS
 ************************************************************/
$notifications = [];
$unseenCount   = 0;

if ($logged_in) {
    try {
        if ($admin === 1) {
            // Admin redz visus
            $sql = "
                SELECT n.*,
                       u.username   AS requestor_name,
                       e.NOSAUKUMS  AS event_name
                  FROM notifications n
             LEFT JOIN users  u ON n.user_id  = u.id
             LEFT JOIN events e ON n.event_id = e.id
              ORDER BY n.created_at DESC
            ";
            $stm = $pdo->prepare($sql);
            $stm->execute();
            $notifications = $stm->fetchAll(PDO::FETCH_ASSOC);

            foreach ($notifications as $nt) {
                if ($nt['seen_by_admin'] == 0) {
                    $unseenCount++;
                }
            }
        } else {
            // Parasts lietotājs => tikai savus paziņojumus
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

            foreach ($notifications as $nt) {
                if ($nt['seen_by_user'] == 0) {
                    $unseenCount++;
                }
            }
        }
    } catch (PDOException $e) {
        die("Paziņojumu iegūšanas kļūda: " . $e->getMessage());
    }
}

/************************************************************
 * (3) IEGŪT PASĀKUMUS + FILTRI
 ************************************************************/
$query  = "SELECT * FROM events WHERE 1=1";
$params = [];

// Nosaukums
if (!empty($_GET['name'])) {
    $query .= " AND NOSAUKUMS LIKE :n";
    $params[':n'] = '%'.$_GET['name'].'%';
}

// Cena (ignorēt negatīvu => uztvert kā 0, ja lietotājs ievada negatīvu)
if (isset($_GET['price'])) {
    $price = (float)$_GET['price'];
    if ($price > 0) {
        $query .= " AND CENA <= :p";
        $params[':p'] = $price;
    }
}

// Vecums (ignorēt negatīvu => uztvert kā 0)
$minAge = isset($_GET['min_age']) ? (int)$_GET['min_age'] : 0;
$maxAge = isset($_GET['max_age']) ? (int)$_GET['max_age'] : 0;

if ($minAge < 0) $minAge = 0;
if ($maxAge < 0) $maxAge = 0;

if ($minAge > 0 && $maxAge > 0) {
    $query .= " AND VECUMS <= :minAge AND VECUMS2 >= :maxAge";
    $params[':minAge'] = $minAge;
    $params[':maxAge'] = $maxAge;
} elseif ($minAge > 0 && $maxAge == 0) {
    $query .= " AND :minAge BETWEEN VECUMS AND VECUMS2";
    $params[':minAge'] = $minAge;
} elseif ($maxAge > 0 && $minAge == 0) {
    $query .= " AND :maxAge BETWEEN VECUMS AND VECUMS2";
    $params[':maxAge'] = $maxAge;
}

// Dzimums
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

// Kategorija
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

// Izpilde
try {
    $stm2 = $pdo->prepare($query);
    $stm2->execute($params);
    $events = $stm2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Pasākumu iegūšanas kļūda: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Labākie bērnu pasākumi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Veiksmes ziņojuma izlidošana
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

        // Pārslēgt paziņojumu nolaižamo logu
        const notifBtn = document.getElementById('notification-btn');
        const notifDrop= document.getElementById('notification-dropdown');
        
        const isAdmin = <?php echo json_encode($admin === 1); ?>;

        if (notifBtn && notifDrop) {
            notifBtn.addEventListener('click', () => {
                notifDrop.classList.toggle('show');
                
                // Ja nav administrators => atzīmēt lietotāja paziņojumus kā redzētus
                if (!isAdmin) {
                    fetch('mark_seen_user.php')
                      .then(response => response.json())
                      .then(data => {
                          if (data.success) {
                              const badge = document.querySelector('.badge');
                              if (badge) {
                                  badge.remove();
                              }
                          }
                      })
                      .catch(err => console.log(err));
                }
            });

            document.addEventListener('click', (e) => {
                if (!notifBtn.contains(e.target) && !notifDrop.contains(e.target)) {
                    notifDrop.classList.remove('show');
                }
            });
        }
    });
    </script>
</head>
<body>
    <header>
        <div class="top-bar">
            <img src="images/logo.png" alt="Logo" class="logo">

            <?php if ($logged_in): ?>
                <div class="user-info">
                    <div class="user-info-top-row">
                        <div class="notification-container">
                            <button id="notification-btn" class="notification-btn">
                                <span class="bell-icon">&#128276;</span>
                                <?php if ($unseenCount > 0): ?>
                                    <span class="badge"><?php echo $unseenCount; ?></span>
                                <?php endif; ?>
                            </button>
                            <div id="notification-dropdown" class="notification-dropdown">
                                <h4>Paziņojumi</h4>
                                <hr>
                                <?php if (empty($notifications)): ?>
                                    <p>No notifications</p>
                                <?php else: ?>
                                    <?php foreach ($notifications as $nt): ?>
                                        <?php
                                        $eventName = $nt['event_name'] ?? 'nezināms pasākums';
                                        $userName  = $nt['requestor_name'] ?? 'nezināms lietotājs';
                                        $status    = $nt['status'];
                                        $nid       = $nt['id'];

                                        // Latvisks statuss
                                        $lvStatus = 'Statuss: Noliegts';
                                        if ($status === 'pending') {
                                            $lvStatus = 'Statuss: Apstrādā';
                                        } elseif ($status === 'accepted') {
                                            $lvStatus = 'Statuss: Apstiprināts';
                                        }
                                        ?>
                                        <div class="notif-item">
                                            <?php
                                            if ($admin === 1) {
                                                if ($status === 'pending') {
                                                    echo "<p><strong>Lietotājs $userName</strong> vēlas pieteikties uz <strong>$eventName</strong></p>";
                                                } elseif ($status === 'accepted') {
                                                    echo "<p><strong>$userName</strong> ir apstiprināts pasākumam <strong>$eventName</strong></p>";
                                                } else {
                                                    echo "<p><strong>$userName</strong> ir noraidīts pasākumam <strong>$eventName</strong></p>";
                                                }
                                            } else {
                                                if ($status === 'pending') {
                                                    echo "<p>Jūsu pieprasījums pievienoties <strong>$eventName</strong> ir apstrādē.</p>";
                                                } elseif ($status === 'accepted') {
                                                    echo "<p>Jūsu pieprasījums pievienoties <strong>$eventName</strong> ir apstiprināts!</p>";
                                                } else {
                                                    echo "<p>Jūsu pieprasījums pievienoties <strong>$eventName</strong> ir noraidīts.</p>";
                                                }
                                            }
                                            ?>
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
                        <a href="index.php?logout=1" class="logout-btn">Izrakstīties</a>
                    </div>
                    <span>Lietotājs: <?php echo htmlspecialchars($username); ?></span>
                </div>
            <?php else: ?>
                <div class="user-info" style="margin-left:auto;">
                    <a href="login.php" class="login-btn">Pierakstīties</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="logo-bar">
            <h1>Bērnu pasākumi</h1>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Sākumlapa</a></li>
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

            <!-- Pasākumu režģis -->
            <?php if (!empty($events)): ?>
            <div class="event-grid">
                <?php foreach ($events as $ev): ?>
                    <?php
                    $eventFolder = 'images/event_'.$ev['id'].'/';
                    $imgs = glob($eventFolder.'*');
                    $firstImg = $imgs ? $imgs[0] : 'placeholder.jpg';
                    $truncatedDescription = (strlen($ev['APRAKSTS']) > 20) 
                        ? substr($ev['APRAKSTS'], 0, 100) . '...' 
                        : $ev['APRAKSTS'];
                    ?>
                    <div class="event-card">
                        <a href="events.php?id=<?php echo htmlspecialchars($ev['id']); ?>">
                            <img src="<?php echo $firstImg; ?>" alt="<?php echo htmlspecialchars($ev['NOSAUKUMS']); ?>">
                            <h3><?php echo htmlspecialchars($ev['NOSAUKUMS']); ?></h3>
                        </a>
                        <p><?php echo htmlspecialchars($truncatedDescription); ?></p>
                        <span class="price">€<?php echo htmlspecialchars($ev['CENA']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="text-align:center; font-size:18px; color:#f00; margin-top:20px;">
                Nav pieejami pasākumi ar šādām prasībām
            </p>
            <?php endif; ?>
        </div>

        <!-- Sānu panelis (FILTER) -->
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
                            <input type="number" id="min-age" name="min_age" placeholder="Min Vecums">
                            <label for="max-age">Max:</label>
                            <input type="number" id="max-age" name="max_age" placeholder="Max Vecums">
                        </div>
                    </div>

                    <div>
                        <label for="category">Kategorija:</label>
                        <div class="category-options">
                            <div class="checkbox-item">
                                <input type="checkbox" id="Maģija" name="category[]" value="Maģija">
                                <label for="Maģija">Maģija</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Princeses" name="category[]" value="Princeses">
                                <label for="Princeses">Princeses</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Kovboji" name="category[]" value="Kovboji">
                                <label for="Kovboji">Kovboji</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Pirāti" name="category[]" value="Pirāti">
                                <label for="Pirāti">Pirāti</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Klauni" name="category[]" value="Klauni">
                                <label for="Klauni">Klauni</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Disko" name="category[]" value="Disko">
                                <label for="Disko">Disko</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Ziemassvētki" name="category[]" value="Ziemassvētki">
                                <label for="Ziemassvētki">Ziemassvētki</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="Burbuļi" name="category[]" value="Burbuļi">
                                <label for="Burbuļi">Burbuļi</label>
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

    
</body>
</html>
