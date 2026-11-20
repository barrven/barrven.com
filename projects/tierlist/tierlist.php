<?php

// Single entrypoint for the whole app. Every page view and every AJAX
// action is requested through this file and dispatched by the `page` /
// `action` query parameters. Files under views/ and api/ check for the
// ENTRY constant and refuse to run if requested directly.

define('ENTRY', true);

// The URL path this script was actually requested through — may be
// /tierlist.php (via the public/ symlink) or /projects/tierlist/tierlist.php
// (direct). Every page/action link is built from this so navigation keeps
// working no matter which URL was used to reach the app. Static assets
// (css/, js/) are plain relative links and resolve on their own as long as
// they're reachable at that same depth (see public/css, public/js symlinks).
define('SELF_URL', $_SERVER['SCRIPT_NAME'] ?? '/tierlist.php');

require_once __DIR__ . '/utils/sessionConfig.php'; // starts the session
require_once __DIR__ . '/utils/dbConnector.php';   // defines APP_ROOT, getPdoObject()
require_once __DIR__ . '/utils/functions.php';     // writeToLog, jsonResponse

$actions = [
    'logout'              => __DIR__ . '/api/logout.php',
    'createList'          => __DIR__ . '/api/createList.php',
    'deleteList'          => __DIR__ . '/api/deleteList.php',
    'addFilesToList'      => __DIR__ . '/api/addFilesToList.php',
    'deleteFilesFromList' => __DIR__ . '/api/deleteFilesFromList.php',
    'removeFromTiers'     => __DIR__ . '/api/removeFromTiers.php',
    'saveTierData'        => __DIR__ . '/api/saveTierData.php',
    'loadTierData'        => __DIR__ . '/api/loadTierData.php',
];

$action = $_GET['action'] ?? null;

if ($action !== null) {
    if (!isset($actions[$action])) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
    }

    require $actions[$action];
    exit;
}

$pages = [
    'login'      => __DIR__ . '/views/login.php',
    'dashboard'  => __DIR__ . '/views/dashboard.php',
    'listEditor' => __DIR__ . '/views/listEditor.php',
];

$defaultPage = isset($_SESSION['userdata']) ? 'dashboard' : 'login';
$page = $_GET['page'] ?? $defaultPage;

if (!isset($pages[$page])) {
    $page = $defaultPage;
}

require $pages[$page];
