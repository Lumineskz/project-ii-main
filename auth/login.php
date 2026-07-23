<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn()) {
    redirect(($_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : '../student/dashboard.php'));
}
$hasAdmin = scalarQuery($conn, "SELECT COUNT(*) FROM users WHERE role = 'admin'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <i class="fa-solid fa-utensils"></i>
      <h2>Welcome back</h2>
      <p>Log in to reserve your next meal.</p>
    </div>

    <?php if (!$hasAdmin): ?>
      <div class="alert alert-warning">
        <i class="fa-solid fa-user-shield"></i>
        No admin account exists yet. <a href="setup_admin.php"><strong>Create the first admin account</strong></a> before logging in.
      </div>
    <?php endif; ?>

    <?php include __DIR__ . '/../includes/flash.php'; ?>

    <form action="login_process.php" method="POST">
      <div class="form-group">
        <label for="email">Email address</label>
        <div class="input-icon">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="you@college.edu" required>
        </div>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-icon">
          <i class="fa-solid fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Log In</button>
    </form>

    <div class="auth-foot">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
</body>
</html>
