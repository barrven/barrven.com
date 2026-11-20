<?php

// ini_set('session.use_only_cookies', 1);
// // ini_set('session.cookie_httponly', 1);
// // ini_set('session.cookie_secure', 1);
// // ini_set('session.cookie_samesite', 'Strict');

// // session_name('tierList_session1');
// session_start();

session_set_cookie_params([
    'lifetime' => 0,           // session cookie
    'path'     => '/',
    'domain'   => '',          // empty = current host (try yourdomain.com if subdomains)
    'secure'   => false,       // ← set true on real HTTPS server
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_name('tierList_session_1');   
session_start();