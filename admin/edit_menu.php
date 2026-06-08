<?php
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/header.php';

if (!isset($conn)) {
    die('Database connection not configured.');
}

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$message = '';

function normalizeImagePath($path) {
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return '';
    }
    if (strpos($path, '../') === 0) {
        return $path;
    }
    if (strpos($path, './') === 0) {
        $path = substr($path, 2);
    }
    if (strpos($path, 'uploads/') === 0) {
        return '../' . $path;
    }
    return $path;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_item'])) {
        $item_id = intval($_POST['delete_item']);
        if ($item_id > 0) {
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE item_id = ?");
            $stmt->bind_param('i', $item_id);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: edit_menu.php?deleted=1');
                exit;
            }
            $message = 'Failed to delete menu item.';
        }
    }


    if (isset($_POST['update_stock'])) {
        $item_id = intval($_POST['update_stock']);
        $available_stock = intval($_POST['available_stock'][$item_id] ?? 0);
        $is_available = isset($_POST['is_available'][$item_id]) ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE menu_items
            SET available_stock = ?, is_available = ?
            WHERE item_id = ?
        ");
        $stmt->bind_param('iii', $available_stock, $is_available, $item_id);

        if ($stmt->execute()) {
            $message = 'Stock and availability updated successfully.';
        } else {
            $message = 'Failed to update stock/availability.';
        }
        $stmt->close();
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $message = 'Menu item deleted successfully.';
}

$selectedItem = null;
if ($item_id > 0) {
    $stmt = $conn->prepare("SELECT item_id, item_name, description, price, available_stock, image, is_available FROM menu_items WHERE item_id = ?");
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedItem = $result->fetch_assoc();
    $stmt->close();

    if (!$selectedItem) {
        header('Location: edit_menu.php');
        exit;
    }
}

$items = [];
$result = $conn->query("SELECT item_id, item_name, description, price, available_stock, image, is_available FROM menu_items ORDER BY item_id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Menu Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

    <div class="content-with-sidebar">
        <div class="page-wrapper">
            <div class="card">
                <h1>Menu Management</h1>
                <?php if ($message !== ''): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <div class="card">
                    <h2>Menu Items</h2>
                    <div class="table-responsive">
                        <form method="post">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Available</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($items) > 0): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                                                <td>
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img class="item-image" src="<?php echo htmlspecialchars(normalizeImagePath($item['image'])); ?>" alt="">
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                                <td>Rs. <?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <input type="number" name="available_stock[<?php echo $item['item_id']; ?>]" min="0" value="<?php echo htmlspecialchars($item['available_stock']); ?>" required>
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="is_available[<?php echo $item['item_id']; ?>]" <?php echo $item['is_available'] ? 'checked' : ''; ?>>
                                                </td>
                                                <td>
                                                    <button type="submit" name="update_stock" value="<?php echo $item['item_id']; ?>">Save</button>
                                                    <button type="submit" name="delete_item" value="<?php echo $item['item_id']; ?>" onclick="return confirm('Are you sure you want to delete this menu item?');">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8">No menu items found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>