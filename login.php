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

            // SQL query to fetch username, password, and admin status
            $sql = "SELECT * FROM USERS WHERE USERNAME='$username'";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);

                // Verify password
                if (password_verify($password, $row["PASSWORD"])) {
                    // Set session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['admin'] = (int)$row['ADMIN']; // Fetch and store admin value

                    // Redirect to the index page
                    header('Location: index.php');
                } else {
                    echo "<p style='color: red; text-align: center;'>Parole ir nepareiza</p>";
                }
            } else {
                echo "<p style='color: red; text-align: center;'>Lietotājvārds vai parole ir nepareiza</p>";
            }
        } else {
            echo "<p style='color: red; text-align: center;'>Lūdzu ievadiet lietotājvārdu vai paroli</p>";
        }
    }
    ?>
</body>
</html>
