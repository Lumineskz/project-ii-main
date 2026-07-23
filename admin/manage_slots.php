<?php
include_once __DIR__ . '/../config/db.php';

include_once __DIR__ . '/../includes/slot_schedule.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$editSlot = null;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_slot'])) {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $slot_name = trim($_POST['slot_name'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($slot_name === '' || $start_time === '' || $end_time === '') {
            $message = 'Slot name, start time and end time are required.';
        } else {
            if ($slot_id > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE order_slots SET slot_name = ?, start_time = ?, end_time = ?, is_active = ? WHERE slot_id = ?");
                mysqli_stmt_bind_param($stmt, 'sssii', $slot_name, $start_time, $end_time, $is_active, $slot_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Timing slot updated successfully.';
                } else {
                    $message = 'Failed to update the slot.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO order_slots (slot_name, start_time, end_time, is_active) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'sssi', $slot_name, $start_time, $end_time, $is_active);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Timing slot created successfully.';
                } else {
                    $message = 'Failed to create the slot.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    if (isset($_POST['delete_slot'])) {
        $slot_id = intval($_POST['delete_slot']);
        $stmt = mysqli_prepare($conn, "DELETE FROM order_slots WHERE slot_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $slot_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Timing slot deleted successfully.';
        } else {
            $message = 'Failed to delete the slot.';
        }
        mysqli_stmt_close($stmt);
    }

    if (isset($_POST['toggle_active'])) {
        $slot_id = intval($_POST['toggle_active']);
        $is_active = intval($_POST['active_state'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE order_slots SET is_active = ? WHERE slot_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $is_active, $slot_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = 'Slot activation updated successfully.';
        } else {
            $message = 'Failed to update slot activation.';
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['slot_id']) && intval($_GET['slot_id']) > 0) {
    $slot_id = intval($_GET['slot_id']);
    $stmt = mysqli_prepare($conn, "SELECT slot_id, slot_name, start_time, end_time, is_active FROM order_slots WHERE slot_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $slot_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editSlot = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$slots = [];
$result = mysqli_query($conn, "SELECT slot_id, slot_name, start_time, end_time, is_active, created_at FROM order_slots ORDER BY start_time ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $slots[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timing Schedule</title>
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
            if (!statusElement) {
                return;
            }

            const now = new Date();
            const matchingSlot = activeSlots.find((slot) => isTimeInSlot(now, slot.start_time, slot.end_time));
            const isClosed = Boolean(matchingSlot);

            if (isClosed) {
                statusElement.className = 'slot-status slot-closed';
                statusElement.innerHTML = 'Orders are currently closed for <strong>' + escapeHtml(matchingSlot.slot_name) + '</strong> until <strong>' + escapeHtml(formatTime(matchingSlot.end_time)) + '</strong>.';
            } else {
                statusElement.className = 'slot-status slot-open';
                const nextSlot = activeSlots.find((slot) => toSeconds(slot.start_time) > (now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds()));
                const nextText = nextSlot
                    ? 'Next closure begins at <strong>' + escapeHtml(formatTime(nextSlot.start_time)) + '</strong> and ends at <strong>' + escapeHtml(formatTime(nextSlot.end_time)) + '</strong>.'
                    : 'No active closing windows are scheduled.';
                statusElement.innerHTML = 'Orders are currently open. ' + nextText;
            }
        }

        document.addEventListener('DOMContentLoaded', updateScheduleStatus);
        window.setInterval(updateScheduleStatus, 30000);
    </script>
</head>
<body class="has-admin-sidebar">
    <?php include '../includes/header.php'; ?>
<main class="main-content content-with-sidebar">
    <div class="page-wrapper">
        <div class="page-header">
            <h1>Order Close Schedule</h1>
            <p>Manage order closure windows. Each slot defines when ordering is closed.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($isOrderWindowClosed): ?>
            <div class="slot-status slot-closed" id="schedule-status">
                Orders are currently closed for <strong><?php echo htmlspecialchars($currentSlot['slot_name']); ?></strong> until <strong><?php echo htmlspecialchars(date('g:i A', strtotime($currentSlot['end_time']))); ?></strong>.
            </div>
        <?php else: ?>
            <div class="slot-status slot-open" id="schedule-status">
                Orders are currently open.
                <?php if ($nextSlot): ?>
                    Next closure begins at <strong><?php echo htmlspecialchars(date('g:i A', strtotime($nextSlot['start_time']))); ?></strong> and ends at <strong><?php echo htmlspecialchars(date('g:i A', strtotime($nextSlot['end_time']))); ?></strong>.
                <?php else: ?>
                    No active closing windows are scheduled.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php echo $editSlot ? 'Edit Close Window' : 'Add New Close Window'; ?></h2>
            <form method="post" class="form-card">
                <input type="hidden" name="slot_id" value="<?php echo htmlspecialchars($editSlot['slot_id'] ?? 0); ?>">
                <div class="form-group">
                    <label for="slot_name">Window Name</label>
                    <input id="slot_name" name="slot_name" type="text" value="<?php echo htmlspecialchars($editSlot['slot_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="start_time">Close Start Time</label>
                    <input id="start_time" name="start_time" type="time" value="<?php echo htmlspecialchars($editSlot['start_time'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_time">Close End Time</label>
                    <input id="end_time" name="end_time" type="time" value="<?php echo htmlspecialchars($editSlot['end_time'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" <?php echo (!isset($editSlot) || $editSlot['is_active']) ? 'checked' : ''; ?>> Active slot
                    </label>
                </div>
                <button type="submit" name="save_slot" class="btn btn-primary"><?php echo $editSlot ? 'Update Slot' : 'Add Slot'; ?></button>
                <?php if ($editSlot): ?>
                    <a href="manage_slots.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Existing Slots</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($slots) === 0): ?>
                            <tr><td colspan="7">No timing slots found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($slots as $slot): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($slot['slot_id']); ?></td>
                                <td><?php echo htmlspecialchars($slot['slot_name']); ?></td>
                                <td><?php echo htmlspecialchars(date('g:i A', strtotime($slot['start_time']))); ?></td>
                                <td><?php echo htmlspecialchars(date('g:i A', strtotime($slot['end_time']))); ?></td>
                                <td><?php echo $slot['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                <td><?php echo htmlspecialchars($slot['created_at']); ?></td>
                                <td class="table-actions">
                                    <a href="manage_slots.php?slot_id=<?php echo $slot['slot_id']; ?>" class="btn btn-secondary">Edit</a>
                                    <form method="post" style="display:inline-flex; gap:0.5rem; margin:0;">
                                        <input type="hidden" name="active_state" value="<?php echo $slot['is_active'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_active" value="<?php echo $slot['slot_id']; ?>" class="btn btn-secondary">
                                            <?php echo $slot['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                        <button type="submit" name="delete_slot" value="<?php echo $slot['slot_id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this slot?');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
