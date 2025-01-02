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
                    $username   = $_POST["username"];
                    $password   = $_POST["password"];
                    $repassword = $_POST["repassword"];

                    if ($password !== $repassword) {
                        echo "Paroles nesakrīt";
                        return;
                    }

                    // Pārbaudīt, vai lietotājvārds jau eksistē
                    $sql = "SELECT * FROM users WHERE username='$username'";
                    $result = mysqli_query($conn, $sql);

                    if (!$result) {
                        echo "Vaicājuma kļūda: " . mysqli_error($conn);
                        return;
                    }

                    if (mysqli_num_rows($result) > 0) {
                        echo "*Lietotājvārds jau eksistē*";
                    } else {
                        // Šifrēt paroli
                        $hash = password_hash($password, PASSWORD_DEFAULT);

                        // Ievietot jauno lietotāju. Ja vēlaties iestatīt 'admin' uz 0 vai 1 pēc noklusējuma,
                        // pielāgojiet laukus atbilstoši. Piemēram:
                        // $sql_insert = "INSERT INTO users (username, password, admin) 
                        //               VALUES ('$username', '$hash', 0)";
                        $sql_insert = "INSERT INTO users (username, password) VALUES ('$username', '$hash')";

                        if (mysqli_query($conn, $sql_insert)) {
                            
                            // Tagad iegūt tikko izveidoto lietotāju, lai dabūtu id & admin
                            $newUserQuery = "SELECT * FROM users WHERE username='$username'";
                            $newUserResult = mysqli_query($conn, $newUserQuery);

                            if ($newUserResult && mysqli_num_rows($newUserResult) > 0) {
                                $row = mysqli_fetch_assoc($newUserResult);
                                
                                // Iestatīt sesijas mainīgos, tāpat kā pieteikšanās procesā
                                $_SESSION['logged_in'] = true;
                                $_SESSION['username']  = $row['username'];
                                $_SESSION['admin']     = (int)$row['admin']; // Pārliecinieties, ka jums ir 'admin' kolonna
                                $_SESSION['user_id']   = $row['id'];

                                // Parādīt veiksmīgas reģistrācijas paziņojumu
                                echo "<div class='success-message'>Reģistrācija veiksmīga! Tiekat novirzīts uz sākumlapu...</div>";

                                // Pāradresēt pēc 3 sekundēm
                                echo "<script>
                                    setTimeout(function() {
                                        window.location.href = 'index.php';
                                    }, 3000);
                                </script>";
                                return;
                            } else {
                                echo "Nevarēja ielādēt jauno lietotāju: " . mysqli_error($conn);
                            }
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
