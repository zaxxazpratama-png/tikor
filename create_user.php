<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $targetId = intval($_POST['user_id'] ?? 0);
        if ($targetId <= 0) {
            $error = 'ID user tidak valid!';
        } elseif ($targetId === intval($_SESSION['user_id'])) {
            $error = 'Anda tidak dapat menghapus akun Anda sendiri!';
        } else {
            try {
                $pdo = getDB();
                // Get target username
                $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $uStmt->execute([$targetId]);
                $targetUser = $uStmt->fetchColumn();

                if ($targetUser) {
                    // Clean up registered devices and active sessions if tables exist
                    try { $pdo->prepare("DELETE FROM registered_devices WHERE user_id = ?")->execute([$targetId]); } catch (Exception $e) {}
                    try { $pdo->prepare("DELETE FROM active_sessions WHERE user_id = ?")->execute([$targetId]); } catch (Exception $e) {}

                    // Delete user
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
                    $success = "User <strong>" . htmlspecialchars($targetUser) . "</strong> berhasil dihapus!";
                } else {
                    $error = "User tidak ditemukan!";
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } else {
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
        .container { max-width: 1050px; margin: 0 auto; padding: 30px 20px; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #38bdf8; text-decoration: none; font-size: 14px; font-weight: 500;
            margin-bottom: 24px; transition: color 0.2s;
        }
        .back-link:hover { color: #7dd3fc; }
        .grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; }
        .card {
            background: rgba(11, 21, 45, 0.95); border-radius: 16px; padding: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.45);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .card-title { font-size: 18px; font-weight: 700; color: #f8fafc; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 11px 14px; background: #0f1c3a;
            border: 1.5px solid #1c325c; border-radius: 10px; font-size: 14px;
            font-family: 'Inter', sans-serif; color: #f8fafc;
            outline: none; transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #0ea5e9; background: #132347;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
        }
        .role-options { display: flex; gap: 12px; }
        .role-option {
            flex: 1; border: 1.5px solid #1c325c; border-radius: 10px; padding: 12px;
            cursor: pointer; transition: all 0.2s; text-align: center;
            background: #0f1c3a;
        }
        .role-option input { display: none; }
        .role-option.selected {
            border-color: #0ea5e9; background: rgba(14, 165, 233, 0.15);
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.3);
        }
        .role-option .role-icon { font-size: 24px; display: block; margin-bottom: 4px; }
        .role-option .role-name { font-size: 13px; font-weight: 600; color: #f8fafc; }
        .btn-submit {
            width: 100%; padding: 13px; background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white; border: none; border-radius: 10px; font-size: 15px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; margin-top: 4px;
            box-shadow: 0 4px 16px rgba(14, 165, 233, 0.35);
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5); }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr {
            background: linear-gradient(135deg, #0c1d3f, #132b5b); color: #38bdf8;
            border-bottom: 1px solid rgba(56, 189, 248, 0.25);
        }
        thead th { padding: 11px 12px; text-align: left; font-weight: 600; }
        tbody tr { border-bottom: 1px solid #132347; }
        tbody tr:hover { background: rgba(14, 165, 233, 0.08); }
        tbody td { padding: 10px 12px; color: #cbd5e1; vertical-align: middle; }
        .badge { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .badge-admin { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-user { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        
        .btn-del {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.35);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        .btn-del:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.4);
        }

        /* Modal */
        .modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.75); z-index: 500;
            align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-backdrop.show { display: flex; }
        .modal {
            background: #0b152d; border-radius: 16px; padding: 32px 28px;
            max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(56, 189, 248, 0.25);
            animation: mIn .2s ease; text-align: center;
        }
        @keyframes mIn { from { transform: scale(.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-icon { font-size: 40px; margin-bottom: 12px; }
        .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #f8fafc; }
        .modal-msg { font-size: 14px; color: #94a3b8; margin-bottom: 24px; line-height: 1.6; }
        .modal-msg strong { color: #f87171; }
        .modal-btns { display: flex; gap: 10px; justify-content: center; }
        .btn-cancel {
            padding: 9px 20px; background: rgba(255, 255, 255, 0.08); color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px;
            font-size: 13px; font-weight: 500; cursor: pointer; font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        .btn-cancel:hover { background: rgba(255, 255, 255, 0.15); color: white; }
        .btn-confirm-del {
            padding: 9px 22px; background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white; border: none; border-radius: 8px; font-size: 13px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35); transition: all 0.2s;
        }
        .btn-confirm-del:hover { background: #dc2626; box-shadow: 0 6px 18px rgba(239, 68, 68, 0.5); }

        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
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
                <input type="hidden" name="action" value="create">
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
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? '👑 Admin' : '👤 User' ?></span></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                            <span style="font-size:11px; color:#64748b; font-style:italic;">(Akun Anda)</span>
                        <?php else: ?>
                            <button type="button" class="btn-del" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                🗑️ Hapus
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal-backdrop" id="delModal">
    <div class="modal">
        <div class="modal-icon">🗑️</div>
        <div class="modal-title">Hapus User?</div>
        <div class="modal-msg" id="delMsg">Apakah Anda yakin ingin menghapus user ini?</div>
        <form method="POST" id="delForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="delUserId" value="">
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-confirm-del">Ya, Hapus</button>
            </div>
        </form>
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

function confirmDelete(id, username) {
    document.getElementById('delUserId').value = id;
    document.getElementById('delMsg').innerHTML = 'Apakah Anda yakin ingin menghapus user <strong>' + username + '</strong>?<br><small style="color:#64748b;">Sesi login dan perangkat terdaftar dari user ini juga akan dihapus.</small>';
    document.getElementById('delModal').classList.add('show');
}
function closeModal() {
    document.getElementById('delModal').classList.remove('show');
}
document.getElementById('delModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
<script src="assets/inactivity.js"></script>
<script src="assets/cookie_consent.js"></script>
</body>
</html>

