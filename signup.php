<?php
session_start();
require './Database/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/signup.css">
    <title>Sign up</title>
</head>
<body>
    <div class="signup-container">
        <h1>Reģistrācija</h1>
        <hr>
        <div class="signup-form">
            <form action="signup.php" method="POST">
                <div class="signup-username">
                    <label for="username">Lietotājvārds</label>
                    <input type="text" id="username" name="username" placeholder="Lietotājvārds">
                </div>

                <div class="signup-password">
                    <label for="password">Parole</label>
                    <input type="password" id="password" name="password" placeholder="Parole">
                </div>

                <div class="signup-repassword">
                    <label for="repassword">Atkārtot paroli</label>
                    <input type="password" id="repassword" name="repassword" placeholder="Atkārtota parole">
                </div>
                
                <div class="signupbtn">
                <button type="submit" name="submit">Reģistrēties</button>
                </div>
            </form>
        </div>
        <div class="notification">
            <?php
            if (isset($_POST["submit"])) {
                if (!empty($_POST["username"]) && !empty($_POST["password"]) && !empty($_POST["repassword"])) {
                    $username = $_POST["username"];
                    $password = $_POST["password"];
                    $repassword = $_POST["repassword"];

                    if ($password !== $repassword) {
                        echo "Paroles nesakrīt";
                        return;
                    }

                    // Check if the username already exists
                    $sql = "SELECT * FROM users WHERE username='$username'";
                    $result = mysqli_query($conn, $sql);

                    if (!$result) {
                        echo "Query error: " . mysqli_error($conn);
                        return;
                    }

                    if (mysqli_num_rows($result) > 0) {
                        echo "*Lietotājvārds jau eksistē*";
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql_insert = "INSERT INTO users (username, password) VALUES ('$username', '$hash')";

                        if (mysqli_query($conn, $sql_insert)) {
                            // Automatically log in the user
                            $_SESSION['logged_in'] = true;
                            $_SESSION['username'] = $username;

                            // Show success message
                            echo "<div class='success-message'>Reģistrācija veiksmīga! Tiekat novirzīts uz sākumlapu...</div>";

                            // Redirect after 3 seconds
                            echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'index.php';
                                }, 3000);
                            </script>";
                            return;
                        } else {
                            echo "Nevarēja reģistrēties: " . mysqli_error($conn);
                        }
                    }
                } else {
                    echo "*Aizpildiet visus lauciņus*";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
