<?php
include '../config/db.php';
include '../includes/slot_schedule.php';

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

$scheduleState = getOrderScheduleState($conn, $currentTime);
$isOrderWindowClosed = $scheduleState['is_closed'];
$currentSlot = $scheduleState['current_slot'];
$nextSlot = $scheduleState['next_slot'];

$activeSlots = [];
$activeSlotsResult = mysqli_query($conn, "SELECT slot_id, slot_name, start_time, end_time FROM order_slots WHERE is_active = 1 ORDER BY start_time ASC");
if ($activeSlotsResult) {
    while ($activeSlot = mysqli_fetch_assoc($activeSlotsResult)) {
        $activeSlots[] = $activeSlot;
    }
    mysqli_free_result($activeSlotsResult);
}
$activeSlotsJson = json_encode($activeSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

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
    <script>
        const activeSlots = <?php echo $activeSlotsJson; ?>;

        function toSeconds(timeValue) {
            const [hours = '0', minutes = '0', seconds = '0'] = String(timeValue || '').split(':');
            return (parseInt(hours, 10) || 0) * 3600 + (parseInt(minutes, 10) || 0) * 60 + (parseInt(seconds, 10) || 0);
        }

        function formatTime(timeValue) {
            const [hours = '0', minutes = '0'] = String(timeValue || '').split(':');
            const date = new Date();
            date.setHours(parseInt(hours, 10) || 0, parseInt(minutes, 10) || 0, 0, 0);
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;');
        }

        function isTimeInSlot(now, startTime, endTime) {
            const current = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
            const start = toSeconds(startTime);
            const end = toSeconds(endTime);

            if (start <= end) {
                return current >= start && current <= end;
            }

            return current >= start || current <= end;
        }

        function updateScheduleStatus() {
            const statusElement = document.getElementById('schedule-status');
            const buttons = Array.from(document.querySelectorAll('.preorder-btn'));
            if (!statusElement) {
                return;
            }

            const now = new Date();
            const matchingSlot = activeSlots.find((slot) => isTimeInSlot(now, slot.start_time, slot.end_time));
            const isClosed = Boolean(matchingSlot);

            if (isClosed) {
                statusElement.className = 'slot-status slot-closed';
                statusElement.innerHTML = 'Orders are currently closed for <strong>' + escapeHtml(matchingSlot.slot_name) + '</strong> until <strong>' + escapeHtml(formatTime(matchingSlot.end_time)) + '</strong>.';
                buttons.forEach((button) => {
                    button.disabled = true;
                    button.textContent = 'Closed';
                });
            } else {
                statusElement.className = 'slot-status slot-open';
                const nextSlot = activeSlots.find((slot) => toSeconds(slot.start_time) > (now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds()));
                const nextText = nextSlot
                    ? 'Next closure begins at <strong>' + escapeHtml(formatTime(nextSlot.start_time)) + '</strong> and ends at <strong>' + escapeHtml(formatTime(nextSlot.end_time)) + '</strong>.'
                    : 'No closing windows are scheduled.';
                statusElement.innerHTML = 'Orders are currently open. ' + nextText;
                buttons.forEach((button) => {
                    button.disabled = false;
                    button.textContent = 'Pre-order';
                });
            }
        }

        document.addEventListener('DOMContentLoaded', updateScheduleStatus);
        window.setInterval(updateScheduleStatus, 30000);
        window.setTimeout(function () {
            window.location.reload();
        }, 60000);
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <?php if ($isOrderWindowClosed && $currentSlot): ?>
        <div class="slot-status slot-closed" id="schedule-status">
            Orders are currently closed for <strong><?php echo htmlspecialchars($currentSlot['slot_name']); ?></strong> until <strong><?php echo htmlspecialchars(date('g:i A', strtotime($currentSlot['end_time']))); ?></strong>.
        </div>
    <?php else: ?>
        <div class="slot-status slot-open" id="schedule-status">
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
