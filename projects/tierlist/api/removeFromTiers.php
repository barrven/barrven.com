<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Direct access not permitted']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$list_id = $data['list_id'] ?? null;
$numFiles = $data['numFiles'] ?? null;

if (!$list_id || !is_numeric($list_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid list ID']);
    exit;
}

if($numFiles === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'numFiles is required']);
    exit;
}


$user_id = $_SESSION['userdata']['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$pdo = getPdoObject();
if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE list_files
        SET file_tier = NULL
        WHERE list_id = ? AND list_id IN (
            SELECT list_id FROM lists WHERE user_id = ?
        )
    ");

    $stmt->execute([$list_id, $user_id]);
    $success = $stmt->rowCount() === $numFiles;

    echo json_encode([
        'success' => $success,
        'error' => $success ? null : 'List not found or not owned by you'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);

    writeToLog("Database error on removeFromTiers: " . $e->getMessage(), APP_ROOT . '/logs/api.log');
}
