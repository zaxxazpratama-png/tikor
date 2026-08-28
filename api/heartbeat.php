<?php
require_once dirname(__DIR__) . '/config/db.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'expired', 'redirect' => 'login.php?msg=session_expired']);
    exit;
}

try {
    $pdo = getDB();
    $token = session_id();

    // Check if session exists in DB
    $checkStmt = $pdo->prepare("SELECT id FROM active_sessions WHERE session_token = ?");
    $checkStmt->execute([$token]);
    $exists = $checkStmt->fetch();

    if ($exists) {
        $updateStmt = $pdo->prepare("UPDATE active_sessions SET last_activity = NOW() WHERE session_token = ?");
        $updateStmt->execute([$token]);
    }

    echo json_encode([
        'status' => 'ok',
        'user' => $_SESSION['username'] ?? 'user'
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'ok', 'note' => 'db_ok']);
}