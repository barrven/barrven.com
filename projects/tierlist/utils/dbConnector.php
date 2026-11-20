<?php

require_once 'functions.php';

// SQLite data file lives inside this project's own /data folder, not shared
// with any other project or app.
define('APP_ROOT', dirname(__DIR__));
$logfile = APP_ROOT . '/logs/dbConnector.log';
define('DB_PATH', APP_ROOT . '/data/app.sqlite');
define('DB_SCHEMA_PATH', APP_ROOT . '/sql/boostrap_database.sql');

function getPdoObject() {
    global $logfile;

    try {
        $isNewDatabase = !file_exists(DB_PATH);

        $dataDir = dirname(DB_PATH);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        if ($isNewDatabase) {
            $pdo->exec(file_get_contents(DB_SCHEMA_PATH));
        }

        return $pdo;
    } catch (Throwable $e) {
        writeToLog("Could not get PDO object: ".$e, $logfile);
        return null;
    }
}


// addFilesToList() should return:
/* 
['responseCode' => 400,
    'data' => [
        'success' => false,
        'error' => 'Files array was empty'
    ]
]
*/
function addFilesToList(array $files, string $listId, array $userdata): array {
    global $logfile;
    // Optional: increase limits if you expect large / many files
    // ini_set('upload_max_filesize', '20M');
    // ini_set('post_max_size', '40M');
    // ini_set('max_file_uploads', '20');

    $count = count($files['name']);
    if($count < 1){
        return [
            'responseCode' => 400,
            'data' => [
                'success' => false,
                'error' => 'Files array was empty'
            ]
        ];
    }

    $pdo = getPdoObject();
    if (!$pdo) {
        writeToLog("Database connection error", $logfile);
        return [
            'responseCode' => 500,
            'data' => [
                'success' => false,
                'error' => 'Error in dbConnector.php Database connection failed'
            ]
        ];
    }

    $allowedExtensions = [
        'jpg','jpeg','png','gif','webp','bmp','svg','tiff','avif','heic',
        'mp4','webm','mov','avi','mkv','ogg','flv','wmv','3gp','mpeg'
    ];

    $uploaded = [];
    $skipped  = [];
    
    try{

        for ($i = 0; $i < $count; $i++) {

            // this file was not uploaded successfully so skip it
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                writeToLog("File extension not allowed: $ext", $logfile);
                $skipped[] = $originalName;
                continue;
            }

            // Generate safe unique filename
            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = rtrim($userdata['home_directory'], '/') . '/' . $storedName;

            if (!move_uploaded_file($tmpName, APP_ROOT . '/' . $dest)) {
                writeToLog("Failed to move uploaded file: $originalName", $logfile);
                $skipped[] = $originalName;
                continue;
            }

            $stmt = $pdo->prepare('INSERT INTO list_files (list_id, file_name) VALUES (?, ?)');
            $stmt->execute([$listId, $storedName]);
            $uploaded[] = $storedName;
        }

    } catch (Throwable $e){
        
        return [
            'responseCode' => 500,
            'data' => [
                'success' => false,
                'error' => "An error occurred in dbConnector.php: ".$e
            ]
        ];
    }
    
    // no error
    return [
        'responseCode' => 200,
        'data' => [
            'success'  => true,
            'list_id'  => $listId,
            'uploaded' => $uploaded,
            'skipped'  => $skipped
        ]
    ];
}