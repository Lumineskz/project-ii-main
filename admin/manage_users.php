<?php
require_once __DIR__ . '/../config/config.php';

requireRole('admin');

$currentPage = 'users';
$pageTitle = 'Manage Users';
$pageSubtitle = 'Recharge balances and manage student & faculty accounts';

$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT u.*,
        sd.student_id, sd.batch, sd.department AS s_department,
        fd.employee_id, fd.subject, fd.department AS f_department
        FROM users u
        LEFT JOIN student_details sd ON sd.user_id = u.id
        LEFT JOIN faculty_details fd ON fd.user_id = u.id
        WHERE u.role != 'admin'";
$params = [];
$types = '';
if ($roleFilter !== '') {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}
if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR sd.student_id LIKE ? OR fd.employee_id LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
$sql .= " ORDER BY u.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <div class="main-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="page-body">
      <?php include __DIR__ . '/../includes/flash.php'; ?>

      <div class="card">
        <form method="GET" class="menu-toolbar">
          <div class="filters">
            <input type="text" name="q" placeholder="Search name, email or ID..." value="<?= e($search) ?>" style="min-width:220px;">
            <select name="role" onchange="this.form.submit()">
              <option value="">All roles</option>
              <option value="student" <?= $roleFilter==='student'?'selected':'' ?>>Student</option>
              <option value="faculty" <?= $roleFilter==='faculty'?'selected':'' ?>>Faculty</option>
            </select>
            <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
          </div>
          <span class="text-muted" style="font-size:.85rem;"><?= mysqli_num_rows($users) ?> user(s)</span>
        </form>

        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>User</th><th>Role / ID</th><th>Contact</th><th>Balance</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($users) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
              <?php endif; ?>
              <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                  <td><strong><?= e($u['full_name']) ?></strong></td>
                  <td>
                    <span class="badge badge-blue"><?= e($u['role']) ?></span><br>
                    <span class="text-muted" style="font-size:.76rem;">
                      <?= $u['role'] === 'student' ? e($u['student_id'] . ' · ' . $u['batch']) : e($u['employee_id'] . ' · ' . $u['subject']) ?>
                    </span>
                  </td>
                  <td style="font-size:.82rem;"><?= e($u['email']) ?><br><span class="text-muted"><?= e($u['phone']) ?></span></td>
                  <td>Rs. <?= number_format($u['balance'], 2) ?></td>
                  <td>
                    <?php if ($u['status'] === 'active'): ?>
                      <span class="badge badge-green">Active</span>
                    <?php else: ?>
                      <span class="badge badge-red">Disabled</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <button class="btn btn-success btn-sm" onclick="openRecharge(<?= $u['id'] ?>, '<?= e(addslashes($u['full_name'])) ?>')">
                        <i class="fa-solid fa-wallet"></i>
                      </button>
                      <form action="user_status_process.php" method="POST">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'disabled' : 'active' ?>">
                        <button type="submit" class="btn btn-<?= $u['status'] === 'active' ? 'warning' : 'outline' ?> btn-sm">
                          <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recharge modal -->
<div class="modal-backdrop" id="rechargeModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-head">
      <h3>Recharge balance</h3>
      <button class="modal-close" data-modal-close><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="recharge_process.php" method="POST">
      <input type="hidden" name="user_id" id="rechargeUserId">
      <p class="text-muted" style="margin-top:-6px;">Adding balance for <strong id="rechargeUserName"></strong></p>
      <div class="form-group">
        <label>Amount (Rs.)</label>
        <input type="number" name="amount" min="1" step="0.01" required placeholder="e.g. 500">
      </div>
      <button type="submit" class="btn btn-success btn-block"><i class="fa-solid fa-wallet"></i> Recharge</button>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openRecharge(id, name) {
  document.getElementById('rechargeUserId').value = id;
  document.getElementById('rechargeUserName').textContent = name;
  document.getElementById('rechargeModal').classList.add('open');
}
</script>
</body>
</html>
