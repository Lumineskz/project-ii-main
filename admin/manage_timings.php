<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$currentPage = 'timings';
$pageTitle = 'Timing Schedules';
$pageSubtitle = 'Control when each meal window opens and when ordering closes';

$schedules = mysqli_query($conn, "SELECT * FROM meal_schedules ORDER BY start_time ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Timing Schedules — <?= e(SITE_NAME) ?></title>
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
        <div class="card-head">
          <div>
            <h2>Meal windows</h2>
            <p>When the close time is reached, all pending reservations for that meal are finalized automatically.</p>
          </div>
          <button class="btn btn-primary btn-sm" data-modal-open="timingModal" onclick="resetTimingForm()">
            <i class="fa-solid fa-plus"></i> Add schedule
          </button>
        </div>

        <div class="table-wrap">
          <table>
            <thead><tr><th>Meal</th><th>Serving window</th><th>Order close time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if (mysqli_num_rows($schedules) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted">No schedules yet. Add breakfast, lunch or dinner timing.</td></tr>
              <?php endif; ?>
              <?php while ($s = mysqli_fetch_assoc($schedules)): ?>
                <tr>
                  <td><strong><?= e($s['meal_name']) ?></strong></td>
                  <td><?= date('g:i A', strtotime($s['start_time'])) ?> – <?= date('g:i A', strtotime($s['end_time'])) ?></td>
                  <td><span class="badge badge-amber"><?= date('g:i A', strtotime($s['order_close_time'])) ?></span></td>
                  <td>
                    <?php if ($s['is_active']): ?><span class="badge badge-green">Active</span>
                    <?php else: ?><span class="badge badge-gray">Inactive</span><?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <button class="btn btn-outline btn-sm" onclick='editTiming(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                      <form action="timings_process.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-<?= $s['is_active'] ? 'warning' : 'success' ?> btn-sm">
                          <i class="fa-solid <?= $s['is_active'] ? 'fa-pause' : 'fa-play' ?>"></i>
                        </button>
                      </form>
                      <form action="timings_process.php" method="POST" onsubmit="return confirm('Delete this schedule?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
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

<div class="modal-backdrop" id="timingModal">
  <div class="modal" style="max-width:460px;">
    <div class="modal-head">
      <h3 id="timingModalTitle">Add schedule</h3>
      <button class="modal-close" data-modal-close><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="timings_process.php" method="POST">
      <input type="hidden" name="action" id="timingAction" value="add">
      <input type="hidden" name="schedule_id" id="scheduleId" value="">
      <div class="form-group">
        <label>Meal name</label>
        <input type="text" name="meal_name" id="mealName" placeholder="e.g. Lunch" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Serving start</label>
          <input type="time" name="start_time" id="startTime" required>
        </div>
        <div class="form-group">
          <label>Serving end</label>
          <input type="time" name="end_time" id="endTime" required>
        </div>
      </div>
      <div class="form-group">
        <label>Order close time <span class="text-muted">(reservations lock at this time)</span></label>
        <input type="time" name="order_close_time" id="closeTime" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Save schedule</button>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function resetTimingForm() {
  document.getElementById('timingModalTitle').textContent = 'Add schedule';
  document.getElementById('timingAction').value = 'add';
  document.getElementById('scheduleId').value = '';
  document.getElementById('mealName').value = '';
  document.getElementById('startTime').value = '';
  document.getElementById('endTime').value = '';
  document.getElementById('closeTime').value = '';
}
function editTiming(s) {
  document.getElementById('timingModalTitle').textContent = 'Edit schedule';
  document.getElementById('timingAction').value = 'update';
  document.getElementById('scheduleId').value = s.id;
  document.getElementById('mealName').value = s.meal_name;
  document.getElementById('startTime').value = s.start_time.substring(0,5);
  document.getElementById('endTime').value = s.end_time.substring(0,5);
  document.getElementById('closeTime').value = s.order_close_time.substring(0,5);
  document.getElementById('timingModal').classList.add('open');
}
</script>
</body>
</html>
