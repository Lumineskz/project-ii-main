<?php
require_once '../config/db.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $available_stock = $_POST['available_stock'];
    $is_available = $_POST['is_available'];

    $image = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

        // Ensure uploads/menu directory exists
        if (!is_dir('../uploads/menu')) {
            mkdir('../uploads/menu', 0755, true);
        }

        $image = "../uploads/menu/" . basename($_FILES['image']['name']);

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $image
        );
    }

    $item_name = mysqli_real_escape_string($conn, $item_name);
    $description = mysqli_real_escape_string($conn, $description);
    $price = floatval($price);
    $available_stock = intval($available_stock);
    $is_available = intval($is_available);
    $image = mysqli_real_escape_string($conn, $image);

    $sql = "INSERT INTO menu_items
            (item_name, description, price, available_stock, image, is_available)
            VALUES ('$item_name', '$description', $price, $available_stock, '$image', $is_available)";

    if (mysqli_query($conn, $sql)) {
        $message = ['type' => 'success', 'text' => 'Item added successfully!'];
    } else {
        $message = ['type' => 'error', 'text' => 'Error: ' . mysqli_error($conn)];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Menu Item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="has-admin-sidebar">
<?php include '../includes/header.php'; ?>
<main class="main-content content-with-sidebar">
    <div class="page-wrapper">
        <div class="form-card add-menu-container">
            <h2>Add Menu Item</h2>
    <?php if (isset($message)): ?>
        <div class="message <?php echo $message['type']; ?>">
            <?php echo $message['text']; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label for="item_name">Item Name:</label>
            <input type="text" id="item_name" name="item_name" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description"></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="available_stock">Available Stock:</label>
            <input
               type="number"
               id="available_stock"
               name="available_stock"
               min="0"
               required>
        </div>

        <div class="form-group">
            <label for="image">Image:</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>

        <div class="form-group">
            <label for="is_available">Availability:</label>
            <select id="is_available" name="is_available">
                <option value="1">Available</option>
                <option value="0">Unavailable</option>
            </select>
        </div>

        <button type="submit">Add Item</button>

    </form>
</div>

</body>
</html>