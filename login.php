<?php
session_start();
include 'config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email == "" || $password == "") {
        $message = "All fields are required.";
    } else {

        if (!$conn) {
            $message = "Database connection failed.";
        } else {

            // SIMPLE procedural query
            $sql = "SELECT user_id, name, email, password, role, balance 
                    FROM users 
                    WHERE email = '$email' 
                    LIMIT 1";

            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) == 1) {

                $user = mysqli_fetch_assoc($result);

                // password check (hashed only)
                if (password_verify($password, $user['password'])) {

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = strtoupper($user['role']);
                    $_SESSION['balance'] = (float)$user['balance'];

                    if ($_SESSION['role'] == "ADMIN") {
                        header("Location: admin/dashboard.php");
                        exit();
                    } else {
                        header("Location: user/dashboard.php");
                        exit();
                    }

                } else {
                    $message = "Incorrect password.";
                }

            } else {
                $message = "No user found with that email.";
            }
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

<div class="login-container">

    <h2>Login</h2>

    <?php if ($message != ""): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="email" name="email" placeholder="Email Address" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>

    </form>

    <a href="index.php" class="back-button">← Back to Home</a>

</div>

</body>
</html>