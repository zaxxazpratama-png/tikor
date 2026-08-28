<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $newPassword = trim($_POST['new_password'] ?? '');
    
    if (!$userId || !$newPassword) {
        $error = 'Pilih user dan masukkan password baru!';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([hash('sha256', $newPassword), $userId]);
            
            $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            
            $success = "Password user <strong>{$user['username']}</strong> berhasil direset!";
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

try {
    $pdo = getDB();
    $users = $pdo->query("SELECT id, username, role FROM users ORDER BY username ASC")->fetchAll();
} catch (Exception $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Support Map</title>
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
        .container { max-width: 600px; margin: 50px auto; padding: 0 20px; }
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
        .card-title { font-size: 22px; font-weight: 700; color: #f8fafc; text-align: center; margin-bottom: 8px; }
        .card-subtitle { color: #94a3b8; text-align: center; font-size: 14px; margin-bottom: 32px; }
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
        .user-select {
            width: 100%; padding: 12px 16px; background: #0f1c3a;
            border: 1.5px solid #1c325c; border-radius: 10px; font-size: 15px;
            font-family: 'Inter', sans-serif; color: #f8fafc;
            outline: none; cursor: pointer; transition: border-color 0.2s;
        }
        .user-select:focus { border-color: #0ea5e9; background: #132347; }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white; border: none; border-radius: 10px; font-size: 16px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; margin-top: 4px;
            box-shadow: 0 4px 16px rgba(14, 165, 233, 0.35);
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5); }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .warning-box {
            background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px;
            color: #fde68a; line-height: 1.6;
        }
        .warning-box strong { display: block; margin-bottom: 4px; color: #fbbf24; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Reset Password</span>
    </div>
    <a href="functions.php" class="btn-header">← Kembali</a>
</div>

<div class="container">
    <a href="functions.php" class="back-link">← Fungsi Lainnya</a>
    <div class="card">
        <div class="card-icon">🔄</div>
        <div class="card-title">Reset Password User</div>
        <div class="card-subtitle">Khusus untuk Admin — reset kata sandi pengguna lain</div>

        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="warning-box">
            <strong>⚠️ Perhatian:</strong>
            Fitur ini akan mengubah password user secara langsung.<br>
            Pastikan Anda memberitahu user terkait password barunya.
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Pilih User</label>
                <select name="user_id" class="user-select" required>
                    <option value="">-- Pilih User --</option>
                    <?php foreach ($users as $u): ?>
                    <?php if ($u['id'] != $_SESSION['user_id']): // Don't show current user ?>
                    <option value="<?= $u['id'] ?>" <?= ($_POST['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?> [<?= $u['role'] ?>]
                    </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <input type="text" name="new_password" class="form-control" 
                       placeholder="Masukkan password baru (min 6 karakter)" required>
            </div>
            <button type="submit" class="btn-submit">🔄 Reset Password</button>
        </form>
    </div>
</div>
<script src="assets/inactivity.js"></script>
</body>
</html>

