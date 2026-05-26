<?php
// register.php

session_start();
include '../includes/header.php';
// Database connection
$message = "";
try {
    include '../config/db.php';
} catch (Throwable $e) {
    // prevent fatal error from stopping the page; provide a friendly message
    $conn = null;
    $message = "Database connection failed. Please check configuration.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Determine available columns and build insert dynamically to avoid "Unknown column" errors
        if ($conn) {
            $colsRes = $conn->query("SHOW COLUMNS FROM users");
            $available = [];
            if ($colsRes) {
                while ($row = $colsRes->fetch_assoc()) {
                    $available[] = $row['Field'];
                }
                $colsRes->free();
            }

            // possible name columns to map full_name to
            $nameCols = ['full_name', 'fullname', 'name', 'username'];
            $useNameCol = null;
            foreach ($nameCols as $c) {
                if (in_array($c, $available)) { $useNameCol = $c; break; }
            }

            $requiredCols = [];
            $params = [];
            $types = '';

            if ($useNameCol) { $requiredCols[] = $useNameCol; $params[] = $full_name; $types .= 's'; }
            if (in_array('email', $available)) { $requiredCols[] = 'email'; $params[] = $email; $types .= 's'; }
            if (in_array('password', $available)) { $requiredCols[] = 'password'; $params[] = $hashed_password; $types .= 's'; }
            if (in_array('role', $available)) { $requiredCols[] = 'role'; $params[] = $role; $types .= 's'; }

            if (count($requiredCols) >= 2) {
                $placeholders = implode(', ', array_fill(0, count($requiredCols), '?'));
                $colList = implode(', ', $requiredCols);
                $sql = "INSERT INTO users ($colList) VALUES ($placeholders)";

                // Build a simple SQL INSERT using escaped values for readability
                $escaped = [];
                foreach ($params as $p) {
                    // use real_escape_string to avoid breaking the query
                    $escaped[] = "'" . $conn->real_escape_string($p) . "'";
                }

                $valuesList = implode(', ', $escaped);
                $sql = "INSERT INTO users ($colList) VALUES ($valuesList)";

                if ($conn->query($sql) === TRUE) {
                    $message = "User registered successfully!";
                } else {
                    $message = "Error: " . $conn->error;
                }
            } else {
                $message = "Database does not have expected columns.";
            }
        } else {
            $message = "Database connection not available.";
        }
    }
}

$conn && method_exists($conn, 'close') ? $conn->close() : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>

    <link rel="stylesheet" type="text/css" href="../css/style.css">
</head>

<body>
<div class="container">
<div class="signup-container">

    <h2>Sign Up</h2>

    <?php if($message != ""): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">

        <input type="text" name="full_name" placeholder="Full Name" required>

        <input type="email" name="email" placeholder="Email Address" required>

        <input type="password" name="password" placeholder="Password" required>

        <select name="role" required>
            <option value="">Select User Role</option>
            <option value="admin">Admin</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
        </select>

        <button type="submit">Register</button>

    </form>

</div>
</div>
</body>
</html>