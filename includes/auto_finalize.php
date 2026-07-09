<?php
date_default_timezone_set('Asia/Kathmandu');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/slot_schedule.php';
file_put_contents(
    __DIR__ . "/finalizer_debug.txt",
    date("Y-m-d H:i:s") . " RAN\n",
    FILE_APPEND
);


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


try {


    $today = date('Y-m-d');


    /*
    |--------------------------------------------------------------------------
    | Find closed slots
    |--------------------------------------------------------------------------
    */

    $now = date("H:i:s");

file_put_contents(
    __DIR__ . "/finalizer_debug.txt",
    "Current PHP Time: ".$now."\n",
    FILE_APPEND
);

$closedSlots = getClosedSlots($conn, $now);
    echo "<pre>";
print_r($closedSlots);
echo "</pre>";
exit;
    require_once __DIR__ . '/finalize_functions.php';
    foreach ($closedSlots as $slot) {
        file_put_contents(
        __DIR__ . "/finalizer_debug.txt",
        "FINALIZING SLOT ".$slot['slot_id']."\n",
        FILE_APPEND
    );

        $slotId = (int)$slot['slot_id'];


        /*
        |--------------------------------------------------------------------------
        | Check if already finalized today
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT log_id
            FROM finalization_logs
            WHERE slot_id=?
            AND finalized_date=?
            LIMIT 1
        ");


        $check->bind_param(
            "is",
            $slotId,
            $today
        );


        $check->execute();

$result = $check->get_result();

file_put_contents(
    __DIR__ . "/finalizer_debug.txt",
    "Slot ".$slotId." log count: ".$result->num_rows."\n",
    FILE_APPEND
);


if ($result->num_rows > 0) {

    file_put_contents(
        __DIR__ . "/finalizer_debug.txt",
        "Skipping slot ".$slotId." because already finalized\n",
        FILE_APPEND
    );

    continue;

}



        /*
        |--------------------------------------------------------------------------
        | Run finalizer
        |--------------------------------------------------------------------------
        */

require_once __DIR__ . '/finalize_functions.php';

file_put_contents(
    __DIR__ . "/finalizer_debug.txt",
    "FINALIZING SLOT: ".$slotId."\n",
    FILE_APPEND
);

finalizeSlot(
    $conn,
    $slotId
);

file_put_contents(
    __DIR__ . "/finalizer_debug.txt",
    "FINISHED SLOT: ".$slotId."\n",
    FILE_APPEND
);



        /*
        |--------------------------------------------------------------------------
        | Save completion log
        |--------------------------------------------------------------------------
        */

        $log = $conn->prepare("
            INSERT INTO finalization_logs
            (
                slot_id,
                finalized_date
            )
            VALUES
            (
                ?,
                ?
            )
        ");


        $log->bind_param(
            "is",
            $slotId,
            $today
        );


        $log->execute();


    }


    echo "Finalization completed";


}


catch(Exception $e){

    die($e->getMessage());

}

?>