<?php
// ─── Session Handler (MySQL-based untuk Vercel serverless) ───────────────────
require_once __DIR__ . '/session.php';
initMysqlSession();

// ─── Database Configuration ───────────────────────────────────────────────────
// Lokal (XAMPP): nilai default digunakan
// Vercel (prod): set via Environment Variables di Vercel Dashboard
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'support_map_db');

// Session timeout: 5 minutes of inactivity
define('SESSION_TIMEOUT', 300);
// Device cookie: lasts 1 year
define('DEVICE_COOKIE_NAME', 'support_map_device');
define('DEVICE_COOKIE_EXPIRE', 86400 * 365);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))        return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get or generate the persistent device token from cookie.
 * Returns existing token, or generates a new one (not yet saved to cookie).
 */
function getDeviceToken() {
    return $_COOKIE[DEVICE_COOKIE_NAME] ?? null;
}

/**
 * Set persistent device cookie on browser (1 year)
 */
function setDeviceCookie($token) {
    setcookie(
        DEVICE_COOKIE_NAME,
        $token,
        time() + DEVICE_COOKIE_EXPIRE,
        '/',
        '',
        false,  // not HTTPS only (local dev)
        true    // httponly
    );
    $_COOKIE[DEVICE_COOKIE_NAME] = $token; // update in current request
}

/**
 * Parse readable device name from User-Agent
 */
function parseDeviceName($ua) {
    $browser = 'Browser';
    if (strpos($ua, 'Chrome') !== false && strpos($ua, 'Edg') === false) $browser = 'Chrome';
    elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) $browser = 'Safari';
    elseif (strpos($ua, 'Edg') !== false) $browser = 'Edge';
    elseif (strpos($ua, 'Opera') !== false) $browser = 'Opera';

    $os = 'Unknown OS';
    if (strpos($ua, 'Windows NT') !== false)  $os = 'Windows';
    elseif (strpos($ua, 'Macintosh') !== false) $os = 'Mac';
    elseif (strpos($ua, 'Android') !== false)   $os = 'Android';
    elseif (strpos($ua, 'iPhone') !== false)    $os = 'iPhone';
    elseif (strpos($ua, 'iPad') !== false)      $os = 'iPad';
    elseif (strpos($ua, 'Linux') !== false)     $os = 'Linux';

    return "$browser ($os)";
}

/**
 * Check if this device is allowed to login for a given user.
 * 
 * Returns:
 *   ['allowed' => true,  'device_token' => '...', 'is_new' => bool]
 *   ['allowed' => false, 'reason' => '...', 'registered_count' => N, 'max' => N]
 */
function checkDeviceAccess($userId) {
    $pdo     = getDB();
    $token   = getDeviceToken();
    $ua      = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip      = getUserIP();

    // Get user's max_devices
    $stmt = $pdo->prepare("SELECT max_devices FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user      = $stmt->fetch();
    $maxDev    = $user['max_devices'] ?? 1;

    // Case 1: Device has a cookie token — check if it's registered to this user
    if ($token) {
        $stmt = $pdo->prepare("SELECT id FROM registered_devices WHERE device_token = ? AND user_id = ?");
        $stmt->execute([$token, $userId]);
        if ($stmt->fetch()) {
            // Known device for this user — always allowed
            // Update last_seen and IP
            $pdo->prepare("UPDATE registered_devices SET last_seen = NOW(), ip_address = ? WHERE device_token = ? AND user_id = ?")
                ->execute([$ip, $token, $userId]);
            return ['allowed' => true, 'device_token' => $token, 'is_new' => false];
        }
        // Cookie exists but belongs to different user OR not registered — treat as new device
    }

    // Case 2: New device — count how many devices this user already has registered
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registered_devices WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= $maxDev) {
        return [
            'allowed'          => false,
            'reason'           => "Akun ini sudah terdaftar di <strong>{$count} perangkat</strong>. Batas maksimal: <strong>{$maxDev} perangkat</strong>.<br>Hubungi Admin untuk menambah akses perangkat baru.",
            'registered_count' => $count,
            'max'              => $maxDev
        ];
    }

    // Case 3: Slot available — generate new device token and register
    $newToken = bin2hex(random_bytes(32)); // 64-char hex token
    $devName  = parseDeviceName($ua);
    $pdo->prepare("INSERT INTO registered_devices (user_id, device_token, device_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)")
        ->execute([$userId, $newToken, $devName, $ip, substr($ua, 0, 500)]);

    return ['allowed' => true, 'device_token' => $newToken, 'is_new' => true];
}

// ─── Session & Auth Helpers ─────────────────────────────────────────────────

function checkLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        $basePath = getenv('VERCEL') ? '' : '/ALATTEMPUR/TIKORSEMIGOOGLE';
        header('Location: ' . $basePath . '/login.php');
        exit;
    }

    // Verify active session still exists in DB (detects admin force-logout)
    try {
        $pdo   = getDB();
        $token = session_id();
        $stmt  = $pdo->prepare("SELECT id FROM active_sessions WHERE session_token = ?");
        $stmt->execute([$token]);
        if (!$stmt->fetch()) {
            session_destroy();
            $basePath = getenv('VERCEL') ? '' : '/ALATTEMPUR/TIKORSEMIGOOGLE';
            header('Location: ' . $basePath . '/login.php?msg=force_logout');
            exit;
        }
    } catch (Exception $e) {
        // DB error — fail open
    }

    updateSessionActivity();
}

function checkAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        $basePath = getenv('VERCEL') ? '' : '/ALATTEMPUR/TIKORSEMIGOOGLE';
        header('Location: ' . $basePath . '/dashboard.php?error=akses_ditolak');
        exit;
    }
}

/**
 * Register a new PHP session into active_sessions table.
 * Called after successful login.
 */
function registerSession($userId) {
    try {
        $pdo  = getDB();
        $tok  = session_id();
        $ip   = getUserIP();
        $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $fp   = hash('sha256', $ip . '|' . ($ua));

        $pdo->prepare("
            INSERT INTO active_sessions (user_id, session_token, device_fingerprint, ip_address, user_agent, last_activity)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_activity = NOW()
        ")->execute([$userId, $tok, $fp, $ip, $ua]);

        $_SESSION['session_token'] = $tok;
    } catch (Exception $e) { /* silent */ }
}

function updateSessionActivity() {
    if (!isset($_SESSION['user_id'])) return;
    if (isset($_SESSION['last_activity_update']) &&
        (time() - $_SESSION['last_activity_update']) < 60) return;

    try {
        $pdo = getDB();
        $pdo->prepare("UPDATE active_sessions SET last_activity = NOW() WHERE session_token = ?")
            ->execute([session_id()]);
        $_SESSION['last_activity_update'] = time();
    } catch (Exception $e) { /* silent */ }
}

function removeSession() {
    try {
        $pdo = getDB();
        $pdo->prepare("DELETE FROM active_sessions WHERE session_token = ?")
            ->execute([session_id()]);
    } catch (Exception $e) { /* silent */ }
}

function cleanExpiredSessions() {
    try {
        $pdo = getDB();
        $pdo->prepare("DELETE FROM active_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)")
            ->execute([SESSION_TIMEOUT]);
    } catch (Exception $e) { /* silent */ }
}
