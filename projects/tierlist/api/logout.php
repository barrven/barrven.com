<?php
if (!defined('ENTRY')) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$_SESSION = array();
session_destroy();
header('Location: ' . SELF_URL . '?page=login');
exit;
