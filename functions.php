<?php
require_once 'config/db.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fungsi Lainnya - Support Map</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #1a1a2e, #2d3561);
            color: white;
            padding: 0 20px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .header-brand { font-size: 18px; font-weight: 700; letter-spacing: 1px; }
        .header-right { display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .btn-header {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-header:hover { background: rgba(255,255,255,0.3); }
        .page-container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .page-subtitle { color: #6b7280; font-size: 14px; margin-bottom: 36px; }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .func-card {
            background: white;
            border-radius: 16px;
            padding: 28px 24px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border: 2px solid transparent;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            display: block;
            position: relative;
            overflow: hidden;
        }
        .func-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
        }
        .func-card.green::before { background: linear-gradient(90deg, #2d6a4f, #40916c); }
        .func-card.blue::before { background: linear-gradient(90deg, #1e3a8a, #3b82f6); }
        .func-card.orange::before { background: linear-gradient(90deg, #c2410c, #f97316); }
        .func-card.purple::before { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
        .func-card.teal::before { background: linear-gradient(90deg, #0f766e, #2dd4bf); }
        .func-card.red::before { background: linear-gradient(90deg, #991b1b, #f87171); }
        .func-card.indigo::before { background: linear-gradient(90deg, #312e81, #6366f1); }
        .func-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-color: #e5e7eb;
        }
        .func-icon {
            font-size: 36px;
            margin-bottom: 14px;
            display: block;
        }
        .func-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }
        .func-desc {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }
        .admin-badge {
            display: inline-block;
            background: #fef3c7;
            color: #d97706;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            margin-top: 8px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #40916c;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">⚙️ FUNGSI LAINNYA</div>
    <div class="header-right">
        <span style="background:rgba(255,255,255,0.1);padding:4px 12px;border-radius:20px;font-size:13px;">
            👤 <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="logout.php" class="btn-header">🚪 Logout</a>
    </div>
</div>

<div class="page-container">
    <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
    <div class="page-title">Fungsi Lainnya</div>
    <div class="page-subtitle">Pilih fungsi yang ingin digunakan</div>

    <div class="cards-grid">
        <a href="change_password.php" class="func-card green">
            <span class="func-icon">🔐</span>
            <div class="func-title">Ganti Password</div>
            <div class="func-desc">Ubah kata sandi akun Anda sendiri</div>
        </a>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="view_data.php" class="func-card blue">
            <span class="func-icon">📊</span>
            <div class="func-title">Lihat Data</div>
            <div class="func-desc">Tampilkan semua data TIKOR yang tersimpan di database</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </a>

        <a href="import_data.php" class="func-card orange">
            <span class="func-icon">📥</span>
            <div class="func-title">Import Data</div>
            <div class="func-desc">Upload file Excel (.xlsx) untuk mengimpor data TIKOR</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </a>

        <a href="create_user.php" class="func-card purple">
            <span class="func-icon">👤</span>
            <div class="func-title">Input User Baru</div>
            <div class="func-desc">Tambahkan akun pengguna baru ke sistem</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </a>

        <a href="download_log.php" class="func-card teal">
            <span class="func-icon">📋</span>
            <div class="func-title">Download Log Login</div>
            <div class="func-desc">Unduh riwayat login semua pengguna (username, IP, waktu)</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </a>

        <a href="reset_password.php" class="func-card red">
            <span class="func-icon">🔄</span>
            <div class="func-title">Reset Password</div>
            <div class="func-desc">Reset kata sandi pengguna lain dalam sistem</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </a>

        <a href="manage_devices.php" class="func-card indigo">
            <span class="func-icon">🖥️</span>
            <div class="func-title">Kelola Device</div>
            <div class="func-desc">Atur batas perangkat per user, lihat sesi aktif, dan force logout</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </a>
        <?php else: ?>
        <div class="func-card indigo" style="opacity:0.5;cursor:not-allowed;">
            <span class="func-icon">🖥️</span>
            <div class="func-title">Kelola Device</div>
            <div class="func-desc">Memerlukan akses admin</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </div>
        <div class="func-card blue" style="opacity:0.5;cursor:not-allowed;">
            <span class="func-icon">📊</span>
            <div class="func-title">Lihat Data</div>
            <div class="func-desc">Memerlukan akses admin</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </div>
        <div class="func-card orange" style="opacity:0.5;cursor:not-allowed;">
            <span class="func-icon">📥</span>
            <div class="func-title">Import Data</div>
            <div class="func-desc">Memerlukan akses admin</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </div>
        <div class="func-card purple" style="opacity:0.5;cursor:not-allowed;">
            <span class="func-icon">👤</span>
            <div class="func-title">Input User Baru</div>
            <div class="func-desc">Memerlukan akses admin</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </div>
        <div class="func-card teal" style="opacity:0.5;cursor:not-allowed;">
            <span class="func-icon">📋</span>
            <div class="func-title">Download Log Login</div>
            <div class="func-desc">Memerlukan akses admin</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </div>
        <div class="func-card red" style="opacity:0.5;cursor:not-allowed;">
            <span class="func-icon">🔄</span>
            <div class="func-title">Reset Password</div>
            <div class="func-desc">Memerlukan akses admin</div>
            <span class="admin-badge">👑 ADMIN ONLY</span>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="/ALATTEMPUR/TIKORSEMIGOOGLE/assets/inactivity.js"></script>
</body>
</html>

