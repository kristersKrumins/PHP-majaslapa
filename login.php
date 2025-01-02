<?php
session_start();
require './Database/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieslēgties</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Pieslēgties</h2>
        
        <!-- Parādīt kļūdu ziņojumus, ja tie ir iestatīti -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="error-message">
                <?php 
                    echo htmlspecialchars($_SESSION['login_error']);
                    unset($_SESSION['login_error']); // noņemt to, lai tas nepastāv ilgstoši
                ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="username">Lietotājvārds:</label>
            <input type="text" id="username" name="username" placeholder="Lietotājvārds" required>

            <label for="password">Parole:</label>
            <input type="password" id="password" name="password" placeholder="Parole" required>

            <input type="submit" name="submit" value="Pieslēgties">
        </form>

        <div class="register-section">
            Nav konta? <a href="SignUp.php">Reģistrēties</a>
        </div>
    </div>

    <?php
    if (isset($_POST["submit"])) {
        if (isset($_POST["username"]) && isset($_POST["password"])) {
            $username = $_POST["username"];
            $password = $_POST["password"];

            // SQL vaicājums, lai iegūtu lietotājvārdu, paroli un admin statusu
            $sql = "SELECT * FROM USERS WHERE USERNAME='$username'";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                // Pārbaudīt paroli
                if (password_verify($password, $row["password"])) {
                    // Iestatīt sesijas mainīgos
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username']  = $username;
                    $_SESSION['admin']    = (int)$row['admin']; // Iegūt un saglabāt admin vērtību
                    $_SESSION['user_id']  = $row['id'];

                    // Pāradresēt uz sākumlapu
                    header('Location: index.php');
                    exit;
                } else {
                    // Nepareiza parole
                    $_SESSION['login_error'] = "Nepareiza parole.";
                    header("Location: login.php");
                    exit;
                }
            } else {
                // Lietotājs nav atrasts
                $_SESSION['login_error'] = "Lietotājvārds vai parole ir nepareiza.";
                header("Location: login.php");
                exit;
            }
        } else {
            // Nav ievadīts lietotājvārds vai parole
            $_SESSION['login_error'] = "Lūdzu ievadiet lietotājvārdu un paroli.";
            header("Location: login.php");
            exit;
        }
    }
    ?>
</body>
</html>
