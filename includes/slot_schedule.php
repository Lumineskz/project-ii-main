<?php

$configuredTimezone = getenv('APP_TIMEZONE');
if ($configuredTimezone === false || $configuredTimezone === '') {
    $configuredTimezone = 'Asia/Kathmandu';
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

    $result = mysqli_query(
    $conn,
    "
    SELECT 
        os.slot_id,
        os.slot_name,
        os.start_time,
        os.end_time

    FROM order_slots os

    WHERE os.is_active = 1

    AND NOT EXISTS (
        SELECT 1
        FROM finalization_logs fl
        WHERE fl.slot_id = os.slot_id
        AND fl.finalized_date = CURDATE()
    )

    ORDER BY os.start_time ASC
    "
);
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

} // <-- closes getOrderScheduleState()


function getClosedSlots($conn, $now = null)
{
    $now = $now ?? date('H:i:s');

    $closedSlots = [];

    $result = mysqli_query(
    $conn,
    "
    SELECT 
        os.slot_id,
        os.slot_name,
        os.start_time,
        os.end_time
    FROM order_slots os
    WHERE os.is_active = 1

    AND NOT EXISTS (
        SELECT 1
        FROM finalization_logs fl
        WHERE fl.slot_id = os.slot_id
        AND fl.finalized_date = CURDATE()
    )
    "
);


    if ($result) {

    //     while ($slot = mysqli_fetch_assoc($result)) {
    //         file_put_contents(
    //     __DIR__ . "/finalizer_debug.txt",
    //     "Checking:\n" . print_r($slot,true) .
    //     "Current: ".$now."\n",
    //     FILE_APPEND
    // );
    //         if (
    //             normalizeSlotTimeValue($now)
    //             >=
    //             normalizeSlotTimeValue($slot['end_time'])
    //         ) {

    //             $closedSlots[] = $slot;

    //         }

    //     }
    while ($slot = mysqli_fetch_assoc($result)) {

    $currentSeconds = normalizeSlotTimeValue($now);
    $endSeconds = normalizeSlotTimeValue($slot['end_time']);

    file_put_contents(
        __DIR__ . "/finalizer_debug.txt",
        "COMPARE: Current=$currentSeconds End=$endSeconds\n",
        FILE_APPEND
    );


    if ($currentSeconds >= $endSeconds) {

        file_put_contents(
            __DIR__ . "/finalizer_debug.txt",
            "ADDING SLOT ".$slot['slot_id']."\n",
            FILE_APPEND
        );

        $closedSlots[] = $slot;

    }

}
        mysqli_free_result($result);
    }


    return $closedSlots;
}

