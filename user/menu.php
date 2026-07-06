<?php
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Search and pagination setup
$itemsPerPage = 16;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$searchQuery = trim($_GET['search'] ?? '');
$offset = ($currentPage - 1) * $itemsPerPage;

$currentTime = date('H:i:s');
$isOrderWindowClosed = false;
$currentSlot = null;
$nextSlot = null;

$activeSlotQuery = mysqli_prepare($conn, "
    SELECT slot_id, slot_name, start_time, end_time
    FROM order_slots
    WHERE is_active = 1
      AND (
            (start_time <= end_time AND start_time <= ? AND end_time >= ?)
            OR (start_time > end_time AND (? >= start_time OR ? <= end_time))
          )
    ORDER BY start_time ASC
    LIMIT 1
");
if ($activeSlotQuery) {
    mysqli_stmt_bind_param($activeSlotQuery, 'ssss', $currentTime, $currentTime, $currentTime, $currentTime);
    mysqli_stmt_execute($activeSlotQuery);
    $slotResult = mysqli_stmt_get_result($activeSlotQuery);
    $currentSlot = mysqli_fetch_assoc($slotResult);
    mysqli_stmt_close($activeSlotQuery);
}

if ($currentSlot) {
    $isOrderWindowClosed = true;
} else {
    $nextSlotQuery = mysqli_prepare($conn, "SELECT slot_id, slot_name, start_time, end_time FROM order_slots WHERE is_active = 1 AND start_time > ? ORDER BY start_time ASC LIMIT 1");
    if ($nextSlotQuery) {
        mysqli_stmt_bind_param($nextSlotQuery, 's', $currentTime);
        mysqli_stmt_execute($nextSlotQuery);
        $nextSlotResult = mysqli_stmt_get_result($nextSlotQuery);
        $nextSlot = mysqli_fetch_assoc($nextSlotResult);
        mysqli_stmt_close($nextSlotQuery);
    }
    if (!$nextSlot) {
        $tomorrowSlotQuery = mysqli_prepare($conn, "SELECT slot_id, slot_name, start_time, end_time FROM order_slots WHERE is_active = 1 ORDER BY start_time ASC LIMIT 1");
        if ($tomorrowSlotQuery) {
            mysqli_stmt_execute($tomorrowSlotQuery);
            $tomorrowSlotResult = mysqli_stmt_get_result($tomorrowSlotQuery);
            $nextSlot = mysqli_fetch_assoc($tomorrowSlotResult);
            mysqli_stmt_close($tomorrowSlotQuery);
        }
    }
}

$searchFilter = '';
if ($searchQuery !== '') {
    $escapedSearch = mysqli_real_escape_string($conn, $searchQuery);
    $searchFilter = " AND (item_name LIKE '%$escapedSearch%' OR description LIKE '%$escapedSearch%')";
}

$totalQuery = "SELECT COUNT(*) AS total FROM menu_items WHERE is_available = 1" . $searchFilter;
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalItems = intval($totalRow['total'] ?? 0);
$totalPages = max(1, ceil($totalItems / $itemsPerPage));

// Ensure current page is within bounds
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $itemsPerPage;
}

$query = "SELECT * FROM menu_items WHERE is_available = 1" . $searchFilter . " ORDER BY item_id ASC LIMIT $itemsPerPage OFFSET $offset";
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
    <?php include '../includes/header.php'; ?>

    <?php if ($isOrderWindowClosed): ?>
        <div class="slot-status slot-closed">
            Orders are currently closed for <strong><?php echo htmlspecialchars($currentSlot['slot_name']); ?></strong> until <strong><?php echo htmlspecialchars(date('g:i A', strtotime($currentSlot['end_time']))); ?></strong>.
        </div>
    <?php else: ?>
        <div class="slot-status slot-open">
            Orders are currently open.
            <?php if ($nextSlot): ?>
                Next closure begins at <strong><?php echo htmlspecialchars(date('g:i A', strtotime($nextSlot['start_time']))); ?></strong> and ends at <strong><?php echo htmlspecialchars(date('g:i A', strtotime($nextSlot['end_time']))); ?></strong>.
            <?php else: ?>
                No closing windows are scheduled.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="search-toolbar">
        <form method="get" class="search-form">
            <input type="search" name="search" class="search-input" placeholder="Search menu items..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="search-button">Search</button>
            <button type="button" class="clear-button" onclick="window.location.href = window.location.pathname;">Clear</button>
        </form>
    </div>
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
                            <button class="preorder-btn" <?php echo ($isOrderWindowClosed || $item['available_stock'] == 0) ? 'disabled' : ''; ?> >
                                <?php echo $isOrderWindowClosed ? 'Closed' : 'Pre-order'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No menu items available at the moment.</p>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination-container" aria-label="Menu pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?search=<?php echo urlencode($searchQuery); ?>&page=<?php echo $currentPage - 1; ?>" class="page-link">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <?php if ($page == $currentPage): ?>
                    <span class="page-link active"><?php echo $page; ?></span>
                <?php else: ?>
                    <a href="?search=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page; ?>" class="page-link"><?php echo $page; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?search=<?php echo urlencode($searchQuery); ?>&page=<?php echo $currentPage + 1; ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</body>
</html>
