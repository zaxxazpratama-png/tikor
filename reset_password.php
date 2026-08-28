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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #991b1b, #f87171);
            color: white; padding: 0 20px; height: 54px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header-brand { font-size: 18px; font-weight: 700; }
        .btn-header { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 5px 14px; border-radius: 6px; font-size: 13px; text-decoration: none; }
        .container { max-width: 600px; margin: 50px auto; padding: 0 20px; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 24px; }
        .card { background: white; border-radius: 16px; padding: 36px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .card-icon { font-size: 40px; text-align: center; margin-bottom: 16px; }
        .card-title { font-size: 22px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 8px; }
        .card-subtitle { color: #6b7280; text-align: center; font-size: 14px; margin-bottom: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif; outline: none; transition: border-color 0.2s; }
        .form-control:focus { border-color: #ef4444; }
        .user-select { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; font-family: 'Inter', sans-serif; outline: none; background: white; cursor: pointer; }
        .user-select:focus { border-color: #ef4444; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #991b1b, #ef4444); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; margin-top: 4px; }
        .btn-submit:hover { opacity: 0.9; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .warning-box { background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: 13px; color: #92400e; line-height: 1.6; }
        .warning-box strong { display: block; margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">🔄 Reset Password</div>
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

