<?php

$configuredTimezone = getenv('APP_TIMEZONE');
if ($configuredTimezone === false || $configuredTimezone === '') {
    $configuredTimezone = 'Asia/Manila';
}
date_default_timezone_set($configuredTimezone);

function normalizeSlotTimeValue($timeValue)
{
    $timeValue = (string) $timeValue;
    if ($timeValue === '') {
        return 0;
    }

    $parts = explode(':', $timeValue);
    $hours = isset($parts[0]) ? (int) $parts[0] : 0;
    $minutes = isset($parts[1]) ? (int) $parts[1] : 0;
    $seconds = isset($parts[2]) ? (int) $parts[2] : 0;

    return ($hours * 3600) + ($minutes * 60) + $seconds;
}

function isTimeInSlot($currentTime, $startTime, $endTime)
{
    $current = normalizeSlotTimeValue($currentTime);
    $start = normalizeSlotTimeValue($startTime);
    $end = normalizeSlotTimeValue($endTime);

    if ($start <= $end) {
        return $current >= $start && $current <= $end;
    }

    return $current >= $start || $current <= $end;
}

function getOrderScheduleState($conn, $now = null)
{
    $now = $now ?? date('H:i:s');
    $currentTimeSeconds = normalizeSlotTimeValue($now);
    $activeSlots = [];

    $result = mysqli_query($conn, "SELECT slot_id, slot_name, start_time, end_time FROM order_slots WHERE is_active = 1 ORDER BY start_time ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $activeSlots[] = $row;
        }
        mysqli_free_result($result);
    }

    $currentSlot = null;
    $nextSlot = null;

    foreach ($activeSlots as $slot) {
        if (isTimeInSlot($now, $slot['start_time'], $slot['end_time'])) {
            $currentSlot = $slot;
            break;
        }
    }

    if (!$currentSlot) {
        foreach ($activeSlots as $slot) {
            if ($currentTimeSeconds < normalizeSlotTimeValue($slot['start_time'])) {
                $nextSlot = $slot;
                break;
            }
        }
    }

    return [
        'is_closed' => (bool) $currentSlot,
        'current_slot' => $currentSlot,
        'next_slot' => $nextSlot,
        'current_time' => $now,
    ];
}
