<?php
/**
 * Run this file ONCE in your browser after importing database.sql to
 * create the first admin account. Delete this file afterwards.
 */
require_once __DIR__ . '/../config/config.php';

$done = false;
$error = '';

// Refuse to run if an admin already exists, to avoid accidental resets.
$existingAdmin = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE role = 'admin'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingAdmin) {
    $name = trim($_POST['full_name'] ?? 'Canteen Admin');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || strlen($password) < 6) {
        $error = 'Please provide a valid email and a password of at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (role, full_name, email, password, balance) VALUES ('admin', ?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash);
        if (mysqli_stmt_execute($stmt)) {
            $done = true;
        } else {
            $error = 'Could not create admin — that email may already be in use.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Setup — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <i class="fa-solid fa-user-shield"></i>
      <h2>First-time admin setup</h2>
      <p>Create the first admin account for <?= e(SITE_NAME) ?>.</p>
    </div>

    <?php if ($existingAdmin): ?>
      <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        An admin account already exists. For security, please <strong>delete this file</strong> (<code>auth/setup_admin.php</code>) from your server.
      </div>
      <a href="login.php" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Go to login</a>
    <?php elseif ($done): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        Admin account created successfully! Please delete <code>auth/setup_admin.php</code> now for security.
      </div>
      <a href="login.php" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Go to login</a>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label>Full name</label>
          <input type="text" name="full_name" value="Canteen Admin" required>
        </div>
        <div class="form-group">
          <label>Email address</label>
          <input type="email" name="email" value="admin@canteen.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Minimum 6 characters" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-user-shield"></i> Create admin account</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
