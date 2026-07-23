<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('manage_menu.php');
}

$action = $_POST['action'] ?? '';

function handleImageUpload() {
    if (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $filename = 'item_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $filename)) {
        return $filename;
    }
    return null;
}

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $availability = in_array($_POST['availability'] ?? '', ['available', 'unavailable']) ? $_POST['availability'] : 'available';

    if ($name === '' || $price < 0 || $stock < 0) {
        setFlash('error', 'Please fill in valid item details.');
        redirect('manage_menu.php');
    }

    $image = handleImageUpload();

    $stmt = mysqli_prepare($conn, "INSERT INTO menu_items (name, description, category, price, image, stock, availability) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sssdsis', $name, $description, $category, $price, $image, $stock, $availability);
    mysqli_stmt_execute($stmt);

    setFlash('success', 'Menu item added.');
} elseif ($action === 'update') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $availability = in_array($_POST['availability'] ?? '', ['available', 'unavailable']) ? $_POST['availability'] : 'available';

    if ($itemId <= 0 || $name === '') {
        setFlash('error', 'Invalid item.');
        redirect('manage_menu.php');
    }

    $image = handleImageUpload();

    if ($image) {
        $stmt = mysqli_prepare($conn, "UPDATE menu_items SET name=?, description=?, category=?, price=?, image=?, stock=?, availability=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssdsisi', $name, $description, $category, $price, $image, $stock, $availability, $itemId);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE menu_items SET name=?, description=?, category=?, price=?, stock=?, availability=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssdisi', $name, $description, $category, $price, $stock, $availability, $itemId);
    }
    mysqli_stmt_execute($stmt);

    setFlash('success', 'Menu item updated.');
} elseif ($action === 'delete') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM menu_items WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $itemId);
    mysqli_stmt_execute($stmt);
    setFlash('success', 'Menu item deleted.');
}

redirect('manage_menu.php');
