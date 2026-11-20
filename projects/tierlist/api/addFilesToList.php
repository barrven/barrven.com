<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Direct access not permitted']);
    exit;
}

define('LOG_FILE', APP_ROOT . '/logs/upload_debug.log');
header('Content-Type: application/json');

if (!isset($_SESSION['userdata'])) {
    jsonResponse(401, ['success' => false, 'error' => 'Not authenticated']);
}
$userdata = $_SESSION['userdata'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    writeToLog("Invalid request method: {$_SERVER['REQUEST_METHOD']}", LOG_FILE);
    jsonResponse(405, ['success' => false,'error' => 'Method not allowed']);
}

$list_id = trim($_POST['list_id'] ?? '');
if ($list_id === '') {
    writeToLog("List name is empty", LOG_FILE);
    jsonResponse(400, ['success' => false,'error' => 'list_id is empty']);
}

if (!isset($_FILES['filesInput'])) {
    writeToLog("filesInput array not set.", LOG_FILE);
    jsonResponse(400, ['success' => false,'error' => 'filesInput not set']);
}

$pdo = getPdoObject();
if (!$pdo) {
    writeToLog("Database connection error", LOG_FILE);
    jsonResponse(500, ['success' => false,'error' => 'Database connection error']);
}


//todo : validate that list_id belongs to this user
//todo : validate that list_id exists

try {
    // add files if any were included
    if(count($_FILES['filesInput']) < 1){
        jsonResponse(200, [
            'success' => true,
            'error' => 'no files to add to list'
        ]);
    }

    $res = addFilesToList($_FILES['filesInput'], $list_id, $userdata);
    jsonResponse($res['responseCode'], $res['data']);

} catch (Throwable $e) {

    writeToLog("Exception: " . $e->getMessage(), LOG_FILE);
    jsonResponse(500, [
        'success' => false,
        'error' => 'Failed to create list'
    ]);
}
