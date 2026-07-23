<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(SITE_NAME) ?> — College Canteen Pre-Order System</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<section class="hero">
  <div class="container">
    <div>
      <span class="hero-eyebrow"><i class="fa-solid fa-bolt"></i> Skip the queue</span>
      <h1>Pre-order your campus meals before the bell rings.</h1>
      <p class="lead">Reserve breakfast, lunch and dinner ahead of time, pay from your canteen balance, and pick it up the moment it's ready — no more standing in line between classes.</p>
      <div class="hero-cta">
        <?php if (!isLoggedIn()): ?>
          <a href="auth/register.php" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Create an account</a>
          <a href="auth/login.php" class="btn btn-outline" style="border-color:rgba(255,255,255,.5);color:rgba(82, 100, 199);"><i class="fa-solid fa-right-to-bracket"></i> Log In</a>
        <?php else: ?>
          <a href="<?= $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php' ?>" class="btn btn-primary"><i class="fa-solid fa-gauge"></i> Go to my dashboard</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="hero-visual">
      <div class="step">
        <i class="fa-solid fa-bowl-food"></i>
        <div><strong>1. Browse the menu</strong><span>See what's cooking and what's in stock, right now.</span></div>
      </div>
      <div class="step">
        <i class="fa-solid fa-cart-shopping"></i>
        <div><strong>2. Reserve your meal</strong><span>Add items to your cart before the ordering window closes.</span></div>
      </div>
      <div class="step">
        <i class="fa-solid fa-wallet"></i>
        <div><strong>3. Balance is deducted</strong><span>Once the window closes, your order is finalized automatically.</span></div>
      </div>
      <div class="step">
        <i class="fa-solid fa-bell-concierge"></i>
        <div><strong>4. Track & collect</strong><span>Watch your order status live and pick it up when it's ready.</span></div>
      </div>
    </div>
  </div>
</section>

<section class="features" id="features">
  <div class="container">
    <div class="section-heading">
      <span class="eyebrow">Why CampusCanteen</span>
      <h2>Everything the canteen needs, in one place</h2>
      <p class="text-muted">One system for students, faculty, and canteen staff — built around real service hours and real kitchen capacity.</p>
    </div>
    <div class="feature-grid">
      <div class="feature-card">
        <i class="fa-solid fa-clock"></i>
        <h3>Timed ordering windows</h3>
        <p>Admins set open and close times for breakfast, lunch and dinner. Menu items lock automatically the moment the window shuts.</p>
      </div>
      <div class="feature-card">
        <i class="fa-solid fa-wallet"></i>
        <h3>Prepaid balance system</h3>
        <p>Recharge your canteen wallet and pay instantly — no cash, no card swiping at the counter.</p>
      </div>
      <div class="feature-card">
        <i class="fa-solid fa-boxes-stacked"></i>
        <h3>Live stock &amp; availability</h3>
        <p>Every reservation checks real kitchen stock, so you never reserve a plate that isn't actually available.</p>
      </div>
      <div class="feature-card">
        <i class="fa-solid fa-chart-line"></i>
        <h3>Admin control center</h3>
        <p>Manage the menu, monitor live orders, recharge student wallets and generate kitchen prep reports from one dashboard.</p>
      </div>
      <div class="feature-card">
        <i class="fa-solid fa-id-card"></i>
        <h3>Role-based accounts</h3>
        <p>Students and faculty get tailored registration forms and dashboards suited to who they are.</p>
      </div>
      <div class="feature-card">
        <i class="fa-solid fa-truck-fast"></i>
        <h3>Live order tracking</h3>
        <p>Follow your order from finalized to preparing to ready for pickup, in real time.</p>
      </div>
    </div>
  </div>
</section>

<section class="how-it-works" id="how-it-works">
  <div class="container">
    <div class="section-heading">
      <span class="eyebrow">How it works</span>
      <h2>From reservation to pickup</h2>
    </div>
    <div class="step-row">
      <div class="step-item">
        <div class="num">1</div>
        <h4>Register &amp; recharge</h4>
        <p>Sign up as a student or faculty member and top up your canteen balance.</p>
      </div>
      <div class="step-item">
        <div class="num">2</div>
        <h4>Reserve your meal</h4>
        <p>Add menu items to your cart before the ordering window for that meal closes.</p>
      </div>
      <div class="step-item">
        <div class="num">3</div>
        <h4>Order gets finalized</h4>
        <p>When the window closes, your reservation is confirmed and your balance is deducted.</p>
      </div>
      <div class="step-item">
        <div class="num">4</div>
        <h4>Pick it up</h4>
        <p>Track the live status of your order and collect it once it's marked ready.</p>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
