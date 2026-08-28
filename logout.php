<?php
// Start session FIRST so session_id() is valid
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'config/db.php';

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
