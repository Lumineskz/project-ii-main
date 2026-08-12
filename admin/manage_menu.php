<?php
require_once __DIR__ . '/../config/config.php';

requireRole('admin');

$currentPage = 'menu';
$pageTitle = 'Menu Management';
$pageSubtitle = 'Add items, set prices, stock and availability';

$items = mysqli_query($conn, "SELECT * FROM menu_items ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Management — <?= e(SITE_NAME) ?></title>
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
            <h2>All menu items</h2>
            <p>Every dish available for reservation across all meal windows.</p>
          </div>
          <button class="btn btn-primary btn-sm" data-modal-open="itemModal" onclick="resetItemForm()">
            <i class="fa-solid fa-plus"></i> Add item
          </button>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Item</th><th>Category</th><th>Price</th><th>Stock</th><th>Availability</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($items) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted">No menu items yet. Add your first dish.</td></tr>
              <?php endif; ?>
              <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <tr>
                  <td>
                    <div class="table-item">
                      <img src="<?= $item['image'] ? '../uploads/menu/' . e($item['image']) : placeholderImage() ?>" alt="">
                      <div>
                        <strong><?= e($item['name']) ?></strong><br>
                        <span class="text-muted" style="font-size:.76rem;"><?= e($item['description']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge badge-blue"><?= e($item['category']) ?></span></td>
                  <td>Rs. <?= number_format($item['price'], 2) ?></td>
                  <td><?= (int)$item['stock'] ?></td>
                  <td>
                    <?php if ($item['availability'] === 'available'): ?>
                      <span class="badge badge-green">Available</span>
                    <?php else: ?>
                      <span class="badge badge-red">Unavailable</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <button class="btn btn-outline btn-sm"
                        onclick='editItem(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        <i class="fa-solid fa-pen"></i>
                      </button>
                      <form action="menu_process.php" method="POST" onsubmit="return confirm('Delete this menu item permanently?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
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

<!-- Add / Edit item modal -->
<div class="modal-backdrop" id="itemModal">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTitle">Add menu item</h3>
      <button class="modal-close" data-modal-close><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="menu_process.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="item_id" id="itemId" value="">

      <div class="form-group">
        <label>Item name</label>
        <input type="text" name="name" id="itemName" required placeholder="e.g. Veg Fried Rice">
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="itemDescription" placeholder="Short description shown to students"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" id="itemCategory" placeholder="e.g. Lunch, Snacks, Beverages" required>
        </div>
        <div class="form-group">
          <label>Price (Rs.)</label>
          <input type="number" name="price" id="itemPrice" min="0" step="0.01" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Stock quantity</label>
          <input type="number" name="stock" id="itemStock" min="0" step="1" required>
        </div>
        <div class="form-group">
          <label>Availability</label>
          <select name="availability" id="itemAvailability">
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Item image <span class="text-muted">(optional — placeholder used if empty)</span></label>
        <input type="file" name="image" accept="image/*">
      </div>

      <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Save item</button>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function resetItemForm() {
  document.getElementById('modalTitle').textContent = 'Add menu item';
  document.getElementById('formAction').value = 'add';
  document.getElementById('itemId').value = '';
  document.getElementById('itemName').value = '';
  document.getElementById('itemDescription').value = '';
  document.getElementById('itemCategory').value = '';
  document.getElementById('itemPrice').value = '';
  document.getElementById('itemStock').value = '';
  document.getElementById('itemAvailability').value = 'available';
}
function editItem(item) {
  document.getElementById('modalTitle').textContent = 'Edit menu item';
  document.getElementById('formAction').value = 'update';
  document.getElementById('itemId').value = item.id;
  document.getElementById('itemName').value = item.name;
  document.getElementById('itemDescription').value = item.description || '';
  document.getElementById('itemCategory').value = item.category;
  document.getElementById('itemPrice').value = item.price;
  document.getElementById('itemStock').value = item.stock;
  document.getElementById('itemAvailability').value = item.availability;
  document.getElementById('itemModal').classList.add('open');
}
</script>
</body>
</html>
