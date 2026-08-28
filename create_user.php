<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'user';

    if (!$username || !$password) {
        $error = 'Username dan password harus diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!in_array($role, ['admin', 'user'])) {
        $error = 'Role tidak valid!';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, hash('sha256', $password), $role]);
            $success = "User <strong>$username</strong> berhasil dibuat dengan role <strong>$role</strong>!";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Username '$username' sudah digunakan!";
            } else {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get user list
try {
    $pdo = getDB();
    $users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input User Baru - Support Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo-tin.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            color: white; padding: 0 20px; height: 54px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header-brand {
            font-size: 18px; font-weight: 700;
            display: flex; align-items: center; gap: 10px;
        }
        .header-logo {
            height: 32px; width: auto; object-fit: contain;
            background: white; padding: 3px 8px; border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        .btn-header {
            background: rgba(255,255,255,0.15); color: white;
            border: 1px solid rgba(255,255,255,0.2); padding: 5px 14px;
            border-radius: 6px; font-size: 13px; text-decoration: none;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #7c3aed; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: 400px 1fr; gap: 24px; }
        .card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .card-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 11px 14px; border: 2px solid #e5e7eb;
            border-radius: 10px; font-size: 14px; font-family: 'Inter', sans-serif;
            outline: none; transition: border-color 0.2s;
        }
        .form-control:focus { border-color: #7c3aed; }
        .role-options { display: flex; gap: 12px; }
        .role-option {
            flex: 1; border: 2px solid #e5e7eb; border-radius: 10px; padding: 12px;
            cursor: pointer; transition: all 0.2s; text-align: center;
        }
        .role-option input { display: none; }
        .role-option.selected { border-color: #7c3aed; background: #faf5ff; }
        .role-option .role-icon { font-size: 24px; display: block; margin-bottom: 4px; }
        .role-option .role-name { font-size: 13px; font-weight: 600; color: #374151; }
        .btn-submit {
            width: 100%; padding: 13px; background: linear-gradient(135deg, #7c3aed, #a78bfa);
            color: white; border: none; border-radius: 10px; font-size: 15px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; margin-top: 4px;
        }
        .btn-submit:hover { opacity: 0.9; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr { background: linear-gradient(135deg, #7c3aed, #a78bfa); color: white; }
        thead th { padding: 11px 12px; text-align: left; font-weight: 600; }
        tbody tr { border-bottom: 1px solid #f3f4f6; }
        tbody tr:hover { background: #faf5ff; }
        tbody td { padding: 10px 12px; color: #374151; }
        .badge { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .badge-admin { background: #fef3c7; color: #d97706; }
        .badge-user { background: #f5f3ff; color: #7c3aed; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Input User Baru</span>
    </div>
    <a href="functions.php" class="btn-header">← Kembali</a>
</div>

<div class="container">
    <a href="functions.php" class="back-link">← Fungsi Lainnya</a>
    <div class="grid">
        <!-- Form -->
        <div class="card">
            <div class="card-title">➕ Tambah User Baru</div>
            <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Masukkan username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="password" class="form-control" 
                           placeholder="Minimal 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <div class="role-options">
                        <label class="role-option <?= ($_POST['role'] ?? 'user') === 'user' ? 'selected' : '' ?>" onclick="setRole('user', this)">
                            <input type="radio" name="role" value="user">
                            <span class="role-icon">👤</span>
                            <span class="role-name">User</span>
                        </label>
                        <label class="role-option <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>" onclick="setRole('admin', this)">
                            <input type="radio" name="role" value="admin">
                            <span class="role-icon">👑</span>
                            <span class="role-name">Admin</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Buat User</button>
            </form>
        </div>

        <!-- User list -->
        <div class="card">
            <div class="card-title">📋 Daftar User (<?= count($users) ?>)</div>
            <table>
                <thead><tr><th>No</th><th>Username</th><th>Role</th><th>Dibuat</th></tr></thead>
                <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? '👑 Admin' : '👤 User' ?></span></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function setRole(role, el) {
    document.querySelectorAll('.role-option').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;
}
// Set default
document.querySelector('.role-option input[value="user"]').checked = true;
</script>
<script src="assets/inactivity.js"></script>
</body>
</html>

