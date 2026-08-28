<?php
require_once 'config/db.php';
// Session sudah diinisialisasi oleh initMysqlSession() di dalam db.php
if (session_status() === PHP_SESSION_NONE) session_start();


// Remove active session from DB
removeSession();

// Destroy PHP session
session_destroy();

// Redirect with appropriate message
$reason = $_GET['reason'] ?? '';
if ($reason === 'inactivity') {
    header('Location: login.php?msg=session_expired');
} else {
    header('Location: login.php');
}
exit;
