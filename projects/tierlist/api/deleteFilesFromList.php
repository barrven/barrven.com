<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Direct access not permitted']);
    exit;
}

define('LOG_FILE', APP_ROOT . '/logs/file_delete.log');

if (!isset($_SESSION['userdata'])) {
    jsonResponse(401, [
        'success' => false,
        'error' => 'Not authenticated'
    ]);
}

$userdata = $_SESSION['userdata'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    writeToLog("Invalid request method: {$_SERVER['REQUEST_METHOD']}", LOG_FILE);
    jsonResponse(405, [
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !is_array($input)) {
    jsonResponse(400, [
        'success' => false,
        'error' => 'Invalid JSON'
    ]);
}

$list_id   = $input['list_id']   ?? null;
$filenames = $input['filenames'] ?? null;

if (!$list_id) {
    jsonResponse(400, [
        'success' => false,
        'error' => 'list_id is required'
    ]);
}

if (!is_array($filenames) || empty($filenames)) {
    jsonResponse(400, [
        'success' => false,
        'error' => 'filenames must be a non-empty array'
    ]);
}

$filesToDelete = array_map('trim', $filenames);
$filesToDelete = array_filter($filesToDelete);

if (empty($filesToDelete)) {
    jsonResponse(400, [
        'success' => false,
        'error' => 'No filenames provided to delete.'
    ]);
}

$pdo = getPdoObject();
if (!$pdo) {
    jsonResponse(500, [
        'success' => false,
        'error' => 'Database connection error'
    ]);
}

try {
    $pdo->beginTransaction();

    // Prepare deletion placeholders
    $placeholders = implode(',', array_fill(0, count($filesToDelete), '?'));


    // Delete from database (only files belonging to this list + user)
    $stmt = $pdo->prepare("
        DELETE FROM list_files
        WHERE list_id = ?
        AND file_name IN ($placeholders)
    ");

    // writeToLog($placeholders, LOG_FILE);
    // writeToLog(json_encode(array_merge([$list_id], $filesToDelete)),LOG_FILE);

    $stmt->execute(array_merge([$list_id], $filesToDelete));

    $deletedCount = $stmt->rowCount();
    // writeToLog("Deleted $deletedCount file record(s) from DB for list $list_id", LOG_FILE);

    // Delete actual files from disk
    $basePath = APP_ROOT . '/' . $userdata['home_directory'] . '/';
    foreach ($filesToDelete as $filename) {
        $fullPath = $basePath . $filename;
        if (file_exists($fullPath) && is_file($fullPath)) {
            if (@unlink($fullPath)) {
                // writeToLog("Deleted file from disk: $filename", LOG_FILE);
            } else {
                writeToLog("Failed to delete file from disk: $filename", LOG_FILE);
            }
        } else {
            writeToLog("File not found on disk (already gone?): $filename", LOG_FILE);
        }
    }

    $pdo->commit();
    jsonResponse(200, [
        'success' => true,
        'message' => 'Successfully deleted all selected files from db and disk'
    ]);


} catch (Throwable $e) {
    writeToLog("Exception occurred: " . $e->getMessage(), LOG_FILE);
    jsonResponse(500, [
        'success' => false,
        'error' => 'error deleting from db or disk'
    ]);

}
