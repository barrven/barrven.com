<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Direct access not permitted']);
    exit;
}

$logfile = APP_ROOT . '/logs/upload_debug.log';
header('Content-Type: application/json');

if (!isset($_SESSION['userdata'])) {
    jsonResponse(401, ['success' => false, 'error' => 'Not authenticated']);
}
$userdata = $_SESSION['userdata'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    writeToLog("Invalid request method: {$_SERVER['REQUEST_METHOD']}", $logfile);
    jsonResponse(405, ['success' => false,'error' => 'Method not allowed']);
}

$listName = trim($_POST['listName'] ?? '');
if ($listName === '') {
    writeToLog("List name is empty", $logfile);
    jsonResponse(400, ['success' => false,'error' => 'List name is required']);
}

$pdo = getPdoObject();
if (!$pdo) {
    writeToLog("Database connection error", $logfile);
    jsonResponse(500, ['success' => false,'error' => 'Database connection failed']);
}

try {
    $pdo->beginTransaction();

    // Create list
    $stmt = $pdo->prepare( 'INSERT INTO lists (user_id, list_name) VALUES (?, ?)' );
    $stmt->execute([$userdata['user_id'], $listName]);
    $listId = $pdo->lastInsertId();
    $pdo->commit();


    // Add files to list
    if (!isset($_FILES['filesInput'])) {
        jsonResponse(400, [ 'success' => false, 'error' => 'FilesInput not set' ]);
    }

    // add files if any were included
    if(count($_FILES['filesInput']) < 1){
        jsonResponse(200, [
            'success' => true,
            'error' => 'no files to add to list'
        ]);
    }

    $res = addFilesToList($_FILES['filesInput'], $listId, $userdata);
    jsonResponse($res['responseCode'], $res['data']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    writeToLog("Exception: " . $e->getMessage(), $logfile);

    jsonResponse(500, [
        'success' => false,
        'error' => 'Failed to create list'
    ]);
}
