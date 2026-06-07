<?php
include '../config/db.php';
include '../includes/header.php';
// No need to session_start() here since it's already called in header.php

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch the menu items from the database
$query = "SELECT * FROM menu_items WHERE is_available = 1";
$result = mysqli_query($conn, $query);
$menu_items = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="menu-container">
        <?php if (count($menu_items) > 0): ?>
            <?php foreach ($menu_items as $item): ?>
                <div class="menu-card">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                    <div class="menu-card-content">
                        <div class="menu-card-title"><?php echo htmlspecialchars($item['item_name']); ?></div>
                        <div class="menu-card-description"><?php echo htmlspecialchars($item['description']); ?></div>
                        <div class="menu-card-footer">
                            <div>
                                <div class="menu-card-price">Rs. <?php echo number_format($item['price'], 2); ?></div>
                                <div class="menu-card-stock">Stock: <?php echo $item['available_stock']; ?></div>
                            </div>
                            <button class="preorder-btn" <?php echo $item['available_stock'] == 0 ? 'disabled' : ''; ?>>
                                Pre-order
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No menu items available at the moment.</p>
        <?php endif; ?>
    </div>
</body>
</html>