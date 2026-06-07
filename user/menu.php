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
    <style>
        .menu-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .menu-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
        }
        
        .menu-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .menu-card-content {
            padding: 15px;
        }
        
        .menu-card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .menu-card-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .menu-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .menu-card-price {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .menu-card-stock {
            font-size: 12px;
            color: #999;
        }
        
        .preorder-btn {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .preorder-btn:hover:not(:disabled) {
            background-color: #2980b9;
        }
        
        .preorder-btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
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