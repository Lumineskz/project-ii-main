<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn()) {
    redirect(($_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : '../student/dashboard.php'));
}
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="auth-head">
      <i class="fa-solid fa-user-plus"></i>
      <h2>Create your account</h2>
      <p>Register as a student or a faculty member to start reserving meals.</p>
    </div>

    <?php include __DIR__ . '/../includes/flash.php'; ?>

    <form action="register_process.php" method="POST">

      <div class="role-toggle">
        <label>
          <input type="radio" name="role" value="student" <?= (($old['role'] ?? 'student') === 'student') ? 'checked' : '' ?>>
          <span><i class="fa-solid fa-user-graduate"></i> Student</span>
        </label>
        <label>
          <input type="radio" name="role" value="faculty" <?= (($old['role'] ?? '') === 'faculty') ? 'checked' : '' ?>>
          <span><i class="fa-solid fa-chalkboard-user"></i> Faculty</span>
        </label>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name'] ?? '') ?>" placeholder="Jane Doe" required>
        </div>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="you@college.edu" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="phone">Phone number</label>
          <input type="text" id="phone" name="phone" value="<?= e($old['phone'] ?? '') ?>" placeholder="98XXXXXXXX">
        </div>
        <div></div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
        </div>
      </div>

      <!-- Student-only fields -->
      <div class="role-fields active" id="fields-student">
        <div class="form-row">
          <div class="form-group">
            <label for="student_id">Student ID</label>
            <input type="text" id="student_id" name="student_id" value="<?= e($old['student_id'] ?? '') ?>" placeholder="e.g. STU-2024-0123">
          </div>
          <div class="form-group">
            <label for="batch">Batch / Year</label>
            <input type="text" id="batch" name="batch" value="<?= e($old['batch'] ?? '') ?>" placeholder="e.g. 2024">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?= e($old['department'] ?? '') ?>" placeholder="e.g. Computer Engineering">
          </div>
          <div class="form-group">
            <label for="semester">Semester (optional)</label>
            <input type="text" id="semester" name="semester" value="<?= e($old['semester'] ?? '') ?>" placeholder="e.g. 5th">
          </div>
        </div>
      </div>

      <!-- Faculty-only fields -->
      <div class="role-fields" id="fields-faculty">
        <div class="form-row">
          <div class="form-group">
            <label for="employee_id">Employee ID</label>
            <input type="text" id="employee_id" name="employee_id" value="<?= e($old['employee_id'] ?? '') ?>" placeholder="e.g. FAC-0045">
          </div>
          <div class="form-group">
            <label for="subject">Subject taught</label>
            <input type="text" id="subject" name="subject" value="<?= e($old['subject'] ?? '') ?>" placeholder="e.g. Data Structures">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="fac_department">Department</label>
            <input type="text" id="fac_department" name="fac_department" value="<?= e($old['fac_department'] ?? '') ?>" placeholder="e.g. Computer Engineering">
          </div>
          <div class="form-group">
            <label for="designation">Designation (optional)</label>
            <input type="text" id="designation" name="designation" value="<?= e($old['designation'] ?? '') ?>" placeholder="e.g. Assistant Professor">
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
        <i class="fa-solid fa-user-plus"></i> Create account
      </button>
    </form>

    <div class="auth-foot">
      Already have an account? <a href="login.php">Log in</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
  // Show the correct field block on load based on any pre-selected role
  document.addEventListener('DOMContentLoaded', function () {
    var checked = document.querySelector('input[name="role"]:checked');
    if (checked && checked.value === 'faculty') {
      document.getElementById('fields-student').classList.remove('active');
      document.getElementById('fields-faculty').classList.add('active');
    }
  });
</script>
</body>
</html>
