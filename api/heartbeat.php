<?php
// Heartbeat API - keeps session alive and detects force logout
require_once '../config/db.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'expired', 'redirect' => 'login.php?msg=session_expired']);
    exit;
}

try {
    $pdo = getDB();
    $token = session_id();

    // STEP 1: Check if this session still exists in DB (force logout detection)
    $checkStmt = $pdo->prepare("SELECT id FROM active_sessions WHERE session_token = ?");
    $checkStmt->execute([$token]);
    $exists = $checkStmt->fetch();

    if (!$exists) {
        // Session was deleted by admin (force logout) or never registered
        session_destroy();
        echo json_encode([
            'status' => 'force_logout',
            'redirect' => 'login.php?msg=force_logout'
        ]);
        exit;
    }

    // STEP 2: Update last_activity (session exists, just refresh it)
    $updateStmt = $pdo->prepare("UPDATE active_sessions SET last_activity = NOW() WHERE session_token = ?");
    $updateStmt->execute([$token]);

    $_SESSION['last_activity_update'] = time();

    echo json_encode([
        'status' => 'ok',
        'user' => $_SESSION['username']
    ]);

} catch (Exception $e) {
    // On DB error, don't kick user out — fail open
    echo json_encode(['status' => 'ok', 'note' => 'db_error']);
}
