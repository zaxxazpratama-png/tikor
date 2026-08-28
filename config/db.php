<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Database Configuration ──────────────────────────────────────────────────
// Konfigurasi Database Hosting cPanel:
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'idpanel_tikoruserdb');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Medan2016@');
define('DB_NAME', getenv('DB_NAME') ?: 'idpanel_tikortindb');


define('SESSION_TIMEOUT', 3600); // 1 jam
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

function getDeviceToken() {
    return $_COOKIE[DEVICE_COOKIE_NAME] ?? null;
}

function setDeviceCookie($token) {
    setcookie(
        DEVICE_COOKIE_NAME,
        $token,
        time() + DEVICE_COOKIE_EXPIRE,
        '/',
        '',
        false,
        true
    );
    $_COOKIE[DEVICE_COOKIE_NAME] = $token;
}

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

function checkDeviceAccess($userId) {
    try {
        $pdo     = getDB();
        $token   = getDeviceToken();
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip      = getUserIP();

        $stmt = $pdo->prepare("SELECT max_devices, role, username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user   = $stmt->fetch();
        $maxDev = $user['max_devices'] ?? 5;

        // Admin selalu diizinkan masuk dari perangkat manapun
        if (($user['role'] ?? '') === 'admin' || ($user['role'] ?? '') === 'superadmin' || ($user['username'] ?? '') === 'admin') {
            $newToken = $token ?: bin2hex(random_bytes(32));
            $devName  = parseDeviceName($ua);
            $pdo->prepare("INSERT INTO registered_devices (user_id, device_token, device_name, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE last_seen = NOW(), ip_address = ?")
                ->execute([$userId, $newToken, $devName, $ip, substr($ua, 0, 500), $ip]);
            return ['allowed' => true, 'device_token' => $newToken, 'is_new' => empty($token)];
        }

        if ($token) {
            $stmt = $pdo->prepare("SELECT id FROM registered_devices WHERE device_token = ? AND user_id = ?");
            $stmt->execute([$token, $userId]);
            if ($stmt->fetch()) {
                $pdo->prepare("UPDATE registered_devices SET last_seen = NOW(), ip_address = ? WHERE device_token = ? AND user_id = ?")
                    ->execute([$ip, $token, $userId]);
                return ['allowed' => true, 'device_token' => $token, 'is_new' => false];
            }
        }

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

        $newToken = bin2hex(random_bytes(32));
        $devName  = parseDeviceName($ua);
        $pdo->prepare("INSERT INTO registered_devices (user_id, device_token, device_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userId, $newToken, $devName, $ip, substr($ua, 0, 500)]);

        return ['allowed' => true, 'device_token' => $newToken, 'is_new' => true];
    } catch (Exception $e) {
        // Fallback allow jika tabel belum ada / error
        return ['allowed' => true, 'device_token' => 'default', 'is_new' => false];
    }
}

function checkLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function checkAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $role = $_SESSION['role'] ?? '';
    if ($role !== 'admin' && $role !== 'superadmin') {
        header('Location: dashboard.php?error=akses_ditolak');
        exit;
    }
}

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
    } catch (Exception $e) {}
}

function updateSessionActivity() {
    // optional heartbeat update
}

function removeSession() {
    try {
        $pdo = getDB();
        $pdo->prepare("DELETE FROM active_sessions WHERE session_token = ?")->execute([session_id()]);
    } catch (Exception $e) {}
}

function cleanExpiredSessions() {
    try {
        $pdo = getDB();
        $pdo->prepare("DELETE FROM active_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)")->execute([SESSION_TIMEOUT]);
    } catch (Exception $e) {}
}