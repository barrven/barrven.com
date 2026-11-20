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

$list_id = isset($_GET['list_id']) ? $_GET['list_id'] : null;

if ($list_id === null) {
    echo json_encode(['error' => 'No list_id provided']);
    exit;
}

// fetch data from mysql list_files table using list_id
// and map to tier data structure by the file_tier column
$pdo = getPdoObject();
if (!$pdo) {
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT file_name, file_tier FROM list_files WHERE list_id = ?');
    $stmt->execute([$list_id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tierData = [
        'S' => [],
        'A' => [],
        'B' => [],
        'C' => [],
        'D' => [],
        'L' => []
    ];

    foreach ($files as $file) {
        $tier = $file['file_tier'] ?: 'L';
        if (isset($tierData[$tier])) {
            $tierData[$tier][] = $file['file_name'];
        }
    }

    echo json_encode($tierData);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => 'Error fetching data: ' . $e->getMessage()]);
    exit;
}
