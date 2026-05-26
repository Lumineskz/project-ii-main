<?php
session_start();
include 'includes/header.php';
$message = "";

try {
    include 'config/db.php';
} catch (Throwable $e) {
    $conn = null;
    $message = "Database connection failed.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {

        $message = "All fields are required.";

    } else {

        if (!$conn) {
            $message = "Database connection not available.";
        } else {
            // Use prepared statement to avoid SQL injection
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {

                $user = $result->fetch_assoc();

            // Verify hashed password or plain-text fallback
            if ((isset($user['password']) && password_verify($password, $user['password'])) ||
                (isset($user['password']) && $password === $user['password'])) {

                // Store session data
                $_SESSION['user_id'] = isset($user['id']) ? $user['id'] : '';
                $_SESSION['full_name'] = isset($user['full_name'])
                    ? $user['full_name']
                    : (isset($user['name']) ? $user['name'] : '');

                $_SESSION['email'] = isset($user['email']) ? $user['email'] : '';
                // Normalize role to uppercase
                $role = isset($user['role']) ? strtoupper($user['role']) : '';
                $_SESSION['role'] = $role;

                // Redirect based on normalized role
                if ($role === 'ADMIN') {
                    header("Location: admin/dashboard.php");
                    exit();
                } elseif ($role === 'STUDENT') {
                    header("Location: student/dashboard.php");
                    exit();
                } elseif ($role === 'FACULTY') {
                    header("Location: faculty/dashboard.php");
                    exit();
                } else {
                    $message = "Invalid user role.";
                }

            } else {

                $message = "Incorrect password.";
            }

        } else {

            $message = "User not found.";
        }
    }
    }
}
$conn && method_exists($conn, 'close') ? $conn->close() : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="/Project-II/css/style.css">
</head>
<body>

<div class="login-container">

    <h2>Login</h2>

    <?php if($message != ""): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">

        <input type="email"
               name="email"
               placeholder="Email Address"
               required>

        <input type="password"
               name="password"
               placeholder="Password"
               required>

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>