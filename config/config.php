<?php
/**
 * Global configuration - included at the top of every page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kathmandu');

// BASE_URL is auto-detected from the current request so the app works
// correctly no matter what folder it's installed in (e.g. "/canteen-preorder",
// "/canteen_preorder", or the domain root). This looks at the URL of the
// page that's currently running and strips off the known sub-folders
// (auth/, admin/, student/) to find the project's root URL.
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    foreach (['/auth', '/admin', '/student'] as $knownSubfolder) {
        if (substr($scriptDir, -strlen($knownSubfolder)) === $knownSubfolder) {
            $scriptDir = substr($scriptDir, 0, -strlen($knownSubfolder));
            break;
        }
    }
    define('BASE_URL', rtrim($scriptDir, '/'));
}
// If auto-detection ever gives the wrong path on your host (e.g. because
// of a proxy or custom rewrite rules), you can force it manually instead —
// just delete the block above and use this line instead:
// define('BASE_URL', '/canteen_preorder');

define('UPLOAD_DIR', __DIR__ . '/../uploads/menu/');
define('UPLOAD_URL', BASE_URL . '/uploads/menu/');

define('SITE_NAME', 'Click2Eat');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Auto-finalize any meal schedules whose order-close time has passed.
// Cheap to run on every request; guarded by the processed_schedules table.
finalizeDueSchedules($conn);
