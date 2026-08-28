<?php
require_once 'config/db.php';
checkLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!$oldPass || !$newPass || !$confirmPass) {
        $error = 'Semua field harus diisi!';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Password baru dan konfirmasi tidak cocok!';
    } elseif (strlen($newPass) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && $user['password'] === hash('sha256', $oldPass)) {
                $newHash = hash('sha256', $newPass);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$newHash, $_SESSION['user_id']]);
                $success = 'Password berhasil diubah!';
            } else {
                $error = 'Password lama tidak benar!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Support Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo-tin.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #070e22 0%, #030712 100%);
            min-height: 100vh;
            color: #f1f5f9;
        }
        .header {
            background: linear-gradient(135deg, #040817 0%, #0b1836 100%);
            color: white; padding: 0 20px; height: 56px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .header-brand {
            font-size: 18px; font-weight: 700;
            display: flex; align-items: center; gap: 10px;
            color: #f8fafc;
        }
        .header-logo {
            height: 32px; width: auto; object-fit: contain;
            background: rgba(255, 255, 255, 0.95); padding: 3px 8px; border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .btn-header {
            background: rgba(255,255,255,0.08); color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.18); padding: 6px 14px;
            border-radius: 8px; font-size: 13px; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-header:hover {
            background: rgba(14, 165, 233, 0.2);
            border-color: rgba(56, 189, 248, 0.4);
            color: white;
        }
        .container { max-width: 520px; margin: 60px auto; padding: 0 20px; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #38bdf8; text-decoration: none; font-size: 14px; font-weight: 500;
            margin-bottom: 24px; transition: color 0.2s;
        }
        .back-link:hover { color: #7dd3fc; }
        .card {
            background: rgba(11, 21, 45, 0.95); border-radius: 18px; padding: 36px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.45);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .card-icon { font-size: 40px; text-align: center; margin-bottom: 16px; }
        .card-title { font-size: 22px; font-weight: 700; color: #f8fafc; text-align: center; margin-bottom: 6px; }
        .card-subtitle { color: #94a3b8; text-align: center; font-size: 14px; margin-bottom: 32px; }
        .card-subtitle strong { color: #38bdf8; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 12px 16px; background: #0f1c3a;
            border: 1.5px solid #1c325c; border-radius: 10px; font-size: 15px;
            font-family: 'Inter', sans-serif; color: #f8fafc;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #0ea5e9; background: #132347;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
        }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white; border: none; border-radius: 10px; font-size: 16px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
            transition: transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(14, 165, 233, 0.35);
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5); }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Ganti Password</span>
    </div>
    <a href="functions.php" class="btn-header">← Kembali</a>
</div>

<div class="container">
    <a href="functions.php" class="back-link">← Fungsi Lainnya</a>
    <div class="card">
        <div class="card-icon">🔐</div>
        <div class="card-title">Ganti Password</div>
        <div class="card-subtitle">Ubah kata sandi akun: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>

        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Password Lama</label>
                <input type="password" name="old_password" class="form-control" placeholder="Masukkan password lama" required>
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
            </div>
            <button type="submit" class="btn-submit">Simpan Password Baru</button>
        </form>
    </div>
</div>
<script src="assets/inactivity.js"></script>
<script src="assets/cookie_consent.js"></script>
</body>
</html>

