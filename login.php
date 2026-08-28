<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Map - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif; min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: rgba(255,255,255,0.97); border-radius: 16px;
            padding: 48px 40px; width: 100%; max-width: 440px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        .login-logo { text-align: center; margin-bottom: 32px; }
        .login-logo .icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            border-radius: 16px; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 16px; font-size: 28px;
        }
        .login-logo h1 { font-size: 24px; font-weight: 700; color: #1a1a2e; }
        .login-logo p { color: #6b7280; font-size: 14px; margin-top: 4px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb;
            border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus { border-color: #40916c; box-shadow: 0 0 0 3px rgba(64,145,108,0.15); }
        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: transform 0.1s, box-shadow 0.2s; margin-top: 8px;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(64,145,108,0.4); }
        .alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        .alert-danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .alert-info    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .device-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 12px 14px; margin-top: 10px; font-size: 12px; color: #64748b; line-height: 1.7;
        }
        .device-box strong { color: #374151; }
        .footer-text { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 24px; }
    </style>
</head>
<body>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/db.php';

$error    = '';
$errType  = 'danger';
$infoMsg  = '';

// URL messages
$urlMsg = $_GET['msg'] ?? '';
if ($urlMsg === 'force_logout')    $infoMsg = '⚠️ Sesi Anda dihentikan oleh Admin. Silakan login kembali.';
if ($urlMsg === 'session_expired') $infoMsg = 'ℹ️ Sesi Anda telah berakhir. Silakan login kembali.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = getUserIP();

    if (!$username || !$password) {
        $error = 'Harap isi username dan password!';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['password'] === hash('sha256', $password)) {

                // ── DEVICE CHECK ──────────────────────────────────────────
                $check = checkDeviceAccess($user['id']);

                if (!$check['allowed']) {
                    $errType = 'warning';
                    $error   = $check['reason'];

                    // Log failed attempt
                    $pdo->prepare("INSERT INTO login_logs (username, password_used, ip_address, login_status) VALUES (?,?,?,'failed')")
                        ->execute([$username, $password, $ip]);

                } else {
                    // ── LOGIN SUCCESS ─────────────────────────────────────
                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['username']    = $user['username'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['max_devices'] = $user['max_devices'];

                    // Set persistent device cookie if new device
                    if ($check['is_new'] || !isset($_COOKIE[DEVICE_COOKIE_NAME])) {
                        setDeviceCookie($check['device_token']);
                    }

                    // Register this PHP session (for force-logout detection)
                    registerSession($user['id']);

                    // Log login
                    $pdo->prepare("INSERT INTO login_logs (username, password_used, ip_address, login_status) VALUES (?,?,?,'success')")
                        ->execute([$username, $password, $ip]);

                    header('Location: dashboard.php');
                    exit;
                }

            } else {
                $error = 'Username atau password salah!';
                try {
                    $pdo->prepare("INSERT INTO login_logs (username, password_used, ip_address, login_status) VALUES (?,?,?,'failed')")
                        ->execute([$username, $password, $ip]);
                } catch(Exception $e) {}
            }
        } catch(Exception $e) {
            $error = 'Koneksi database gagal. Pastikan MySQL berjalan.';
        }
    }
}
?>
    <div class="login-card">
        <div class="login-logo">
            <div class="icon">🗺️</div>
            <h1>Support Login</h1>
            <p>TIKOR Support Map System</p>
        </div>

        <?php if ($infoMsg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($infoMsg) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-<?= $errType ?>">
            <?php if ($errType === 'warning'): ?>
            🔒 <strong>Perangkat Tidak Diizinkan</strong><br>
            <?= $error ?>
            <div class="device-box">
                <strong>Perangkat Anda saat ini:</strong><br>
                📱 <?= htmlspecialchars(parseDeviceName($_SERVER['HTTP_USER_AGENT'] ?? '')) ?><br>
                🌐 IP: <?= htmlspecialchars(getUserIP()) ?><br><br>
                Hubungi <strong>Admin</strong> untuk mendaftarkan perangkat baru ini.
            </div>
            <?php else: ?>
            <?= htmlspecialchars($error) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Masukkan username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Masukkan password"
                       autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <p class="footer-text">© 2026 Support Map System</p>
    </div>
</body>
</html>
