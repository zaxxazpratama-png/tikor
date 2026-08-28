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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #1a1a2e, #2d3561);
            color: white; padding: 0 20px; height: 54px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header-brand { font-size: 18px; font-weight: 700; }
        .btn-header {
            background: rgba(255,255,255,0.15); color: white;
            border: 1px solid rgba(255,255,255,0.2); padding: 5px 14px;
            border-radius: 6px; font-size: 13px; text-decoration: none;
        }
        .container { max-width: 520px; margin: 60px auto; padding: 0 20px; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #40916c; text-decoration: none; font-size: 14px; font-weight: 500;
            margin-bottom: 24px;
        }
        .card {
            background: white; border-radius: 16px; padding: 36px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .card-icon { font-size: 40px; text-align: center; margin-bottom: 16px; }
        .card-title { font-size: 22px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 6px; }
        .card-subtitle { color: #6b7280; text-align: center; font-size: 14px; margin-bottom: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb;
            border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s;
        }
        .form-control:focus { border-color: #40916c; }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white; border: none; border-radius: 10px; font-size: 16px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(64,145,108,0.35); }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">🔐 Ganti Password</div>
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
<script src="/ALATTEMPUR/TIKORSEMIGOOGLE/assets/inactivity.js"></script>
</body>
</html>

