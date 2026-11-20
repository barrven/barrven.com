<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Direct access not permitted']);
    exit;
}

if (!isset($_SESSION['userdata'])) {
    header('Location: ' . SELF_URL . '?page=login');
    exit;
}

header('Content-Type: application/json');

define('LOG_FILE', APP_ROOT . '/logs/save_tierdata.log');

// get the request body and convert to php associative array
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$list_id  = $data['list_id'] ?? null;
$tierData = $data['tierData'] ?? null;

// tierData format:
// $tierData = [
//     'S' => [],
//     'A' => [],
//     'B' => [],
//     'C' => [],
//     'D' => [],
//     'L' => []
// ];
//access with for example $tierData['S'][0]

if ($list_id === null || $tierData === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}


$files = [];
foreach (array_keys($tierData) as $key) {

    foreach ($tierData[$key] as $path){
        $files[] = [
            'tier' => $key,
            'name' => basename($path)
        ];

    }
}

// foreach ($files as $file) {
//     writeToLog($file['tier'], LOG_FILE);
//     writeToLog($list_id, LOG_FILE);
//     writeToLog($file['name'], LOG_FILE);
// }

$pdo = getPdoObject();
if (!$pdo) {
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "UPDATE list_files
        SET file_tier = :tier
        WHERE list_id = :list_id
        AND file_name = :file_name"
    );

    $totalUpdated = 0;
    foreach ($files as $file) {
        writeToLog('queuing '.$file['name'], LOG_FILE);

        $stmt->execute([
            ':tier'      => $file['tier'],
            ':list_id'   => $list_id,
            ':file_name' => $file['name'],
        ]);

        $totalUpdated += $stmt->rowCount();
    }

    writeToLog('completed queue, updated: '.$totalUpdated. ' out of '.count($files).' updated', LOG_FILE);

    $pdo->commit();

    writeToLog('completed', LOG_FILE);

    http_response_code(200);
    echo json_encode(['error' => false, 'message' => 'Tier data saved']);
}
// clean up, tell the client what failed
catch (Throwable $e) {
    $pdo->rollBack();
    writeToLog('Error saving to db, (changes rolled back): '.$e->getMessage(), LOG_FILE);
    http_response_code(500);
    echo json_encode(['error' => 'Error saving data: ' . $e->getMessage()]);
    //let PHP track the error properly by sending it up the call stack
    throw $e;
}
