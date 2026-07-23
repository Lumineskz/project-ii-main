<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['student', 'faculty']);
refreshSessionBalance($conn, $_SESSION['user_id']);

$currentPage = 'menu';
$pageTitle = 'Menu';
$pageSubtitle = 'Reserve your meal before the ordering window closes';

$openSchedules = getAllOpenSchedules($conn);
$openScheduleList = [];
while ($s = mysqli_fetch_assoc($openSchedules)) $openScheduleList[] = $s;

$items = mysqli_query($conn, "SELECT * FROM menu_items WHERE availability = 'available' ORDER BY category, name");
$itemList = [];
$categories = [];
while ($item = mysqli_fetch_assoc($items)) {
    $itemList[] = $item;
    if (!in_array($item['category'], $categories)) $categories[] = $item['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu — <?= e(SITE_NAME) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://kit.fontawesome.com/5f3c0ac785.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

  <div class="main-col">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="page-body">
      <?php include __DIR__ . '/../includes/flash.php'; ?>

      <?php if (empty($openScheduleList)): ?>
        <div class="alert alert-warning">
          <i class="fa-solid fa-triangle-exclamation"></i>
          No ordering window is currently open. You can still browse the menu, but reservations are closed until the next window opens.
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="menu-toolbar">
          <div class="filters" id="categoryFilters">
            <button type="button" class="chip-filter active" data-cat="all">All items</button>
            <?php foreach ($categories as $cat): ?>
              <button type="button" class="chip-filter" data-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
            <?php endforeach; ?>
          </div>

          <?php if (!empty($openScheduleList)): ?>
            <div class="input-icon" style="max-width:260px;">
              <i class="fa-solid fa-clock"></i>
              <select id="scheduleSelect">
                <?php foreach ($openScheduleList as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= e($s['meal_name']) ?> — closes <?= date('g:i A', strtotime($s['order_close_time'])) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <input type="hidden" id="activeScheduleId" value="<?= $openScheduleList[0]['id'] ?>">
          <?php endif; ?>
        </div>

        <?php if (empty($itemList)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-bowl-food"></i>
            <p>No menu items available right now. Please check back soon.</p>
          </div>
        <?php else: ?>
          <div class="menu-grid" id="menuGrid">
            <?php foreach ($itemList as $item): ?>
              <div class="menu-card <?= empty($openScheduleList) || $item['stock'] <= 0 ? 'disabled-overlay' : '' ?>"
                   data-item-id="<?= $item['id'] ?>" data-stock="<?= (int)$item['stock'] ?>" data-cat="<?= e($item['category']) ?>">
                <div class="thumb">
                  <img src="<?= $item['image'] ? '../uploads/menu/' . e($item['image']) : placeholderImage() ?>" alt="<?= e($item['name']) ?>">
                  <span class="stock-tag"><?= (int)$item['stock'] ?> left</span>
                </div>
                <div class="body">
                  <h4><?= e($item['name']) ?></h4>
                  <p class="desc"><?= e($item['description']) ?></p>
                  <div class="price-row">
                    <span class="price">Rs. <?= number_format($item['price'], 2) ?></span>
                    <?php if ($item['stock'] > 0 && !empty($openScheduleList)): ?>
                      <div class="qty-control">
                        <button type="button" class="qty-minus">−</button>
                        <span class="qty-value">1</span>
                        <button type="button" class="qty-plus">+</button>
                      </div>
                    <?php endif; ?>
                  </div>
                  <button type="button" class="btn btn-primary btn-sm btn-block add-to-cart-btn" style="margin-top:8px;"
                          <?= ($item['stock'] <= 0 || empty($openScheduleList)) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-cart-plus"></i>
                    <?= $item['stock'] <= 0 ? 'Out of stock' : (empty($openScheduleList) ? 'Ordering closed' : 'Reserve') ?>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
<script>
  // Category filter chips
  document.querySelectorAll('.chip-filter').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.chip-filter').forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
      var cat = chip.getAttribute('data-cat');
      document.querySelectorAll('.menu-card').forEach(function (card) {
        card.style.display = (cat === 'all' || card.getAttribute('data-cat') === cat) ? '' : 'none';
      });
    });
  });

  // Keep hidden schedule id in sync with the dropdown
  var scheduleSelect = document.getElementById('scheduleSelect');
  if (scheduleSelect) {
    scheduleSelect.addEventListener('change', function () {
      document.getElementById('activeScheduleId').value = scheduleSelect.value;
    });
  }
</script>
</body>
</html>
