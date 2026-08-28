<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error    = '';
$errType  = 'danger';
$infoMsg  = '';

$urlMsg = $_GET['msg'] ?? '';
if ($urlMsg === 'force_logout')    $infoMsg = 'Sesi Anda telah dihentikan oleh Admin. Silakan login kembali.';
if ($urlMsg === 'session_expired') $infoMsg = 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = getUserIP();

    if (!$username || !$password) {
        $error = 'Harap isi username dan password!';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            $isValid = false;
            if ($user) {
                if ($user['password'] === hash('sha256', $password) || password_verify($password, $user['password']) || $user['password'] === $password) {
                    $isValid = true;
                }
            }

            if ($isValid) {
                $check = checkDeviceAccess($user['id']);

                if (!$check['allowed']) {
                    $errType = 'warning';
                    $error   = $check['reason'];

                    try {
                        $pdo->prepare("INSERT INTO login_logs (username, password_used, ip_address, login_status) VALUES (?,?,?,'failed')")
                            ->execute([$username, $password, $ip]);
                    } catch (Exception $e) {}

                } else {
                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['username']    = $user['username'];
                    $_SESSION['role']        = ($user['role'] === 'superadmin') ? 'admin' : ($user['role'] ?? 'user');
                    $_SESSION['max_devices'] = $user['max_devices'] ?? 5;

                    if ($check['is_new'] || !isset($_COOKIE[DEVICE_COOKIE_NAME])) {
                        setDeviceCookie($check['device_token']);
                    }

                    registerSession($user['id']);

                    try {
                        $pdo->prepare("INSERT INTO login_logs (username, password_used, ip_address, login_status) VALUES (?,?,?,'success')")
                            ->execute([$username, $password, $ip]);
                    } catch (Exception $e) {}

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
            $error = 'Koneksi database gagal: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Login - Support Map</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo-tin.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 15%, #0e1e45 0%, #060d21 55%, #02050f 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            color: #f1f5f9;
        }
        .login-card {
            background: rgba(11, 21, 45, 0.92);
            border: 1px solid rgba(56, 189, 248, 0.25);
            border-radius: 18px;
            padding: 40px 32px; width: 100%; max-width: 440px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.65), 0 0 40px rgba(14, 165, 233, 0.12);
            backdrop-filter: blur(16px);
        }
        .login-logo { text-align: center; margin-bottom: 28px; }
        .login-logo .logo-img {
            max-width: 140px; max-height: 75px;
            object-fit: contain; display: block;
            margin: 0 auto 16px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 14px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .login-logo h1 { font-size: 19px; font-weight: 700; color: #f8fafc; letter-spacing: 0.5px; }
        .login-logo p { color: #38bdf8; font-size: 13px; margin-top: 4px; font-weight: 500; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 12px 16px;
            background: #0f1c3a;
            border: 1.5px solid #1c325c;
            border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif;
            color: #f8fafc;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-control::placeholder { color: #64748b; }
        .form-control:focus {
            border-color: #0ea5e9;
            background: #132347;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
        }
        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: transform 0.1s, box-shadow 0.2s; margin-top: 8px;
            box-shadow: 0 4px 16px rgba(14, 165, 233, 0.35);
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.5);
        }
        .alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        .alert-danger  { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-warning { background: rgba(245, 158, 11, 0.15); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
        .alert-info    { background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(14, 165, 233, 0.3); }
        .device-box {
            background: rgba(15, 23, 42, 0.6); border: 1px solid #1e293b; border-radius: 8px;
            padding: 12px 14px; margin-top: 10px; font-size: 12px; color: #94a3b8; line-height: 1.7;
        }
        .device-box strong { color: #f8fafc; }
        .footer-text { text-align: center; color: #64748b; font-size: 12px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/logo-tin.png" alt="PT. TALENTA INTEGRITAS NASIONAL" class="logo-img">
            <h1>PT. TALENTA INTEGRITAS NASIONAL</h1>
            <p>Support Map System</p>
        </div>

        <?php if ($infoMsg): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($infoMsg) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-<?= $errType ?>">
            <?php if ($errType === 'warning'): ?>
            <strong>Peringatan: Perangkat Tidak Diizinkan</strong><br>
            <?= $error ?>
            <div class="device-box">
                <strong>Perangkat Anda saat ini:</strong><br>
                • Perangkat: <?= htmlspecialchars(parseDeviceName($_SERVER['HTTP_USER_AGENT'] ?? '')) ?><br>
                • IP: <?= htmlspecialchars(getUserIP()) ?><br><br>
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
                       autocomplete="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Masukkan password"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <p class="footer-text">&copy; 2026 Support Map System - PT. TALENTA INTEGRITAS NASIONAL</p>
    </div>
</body>
</html>