<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

// Handle CSV download
if (isset($_GET['download'])) {
    try {
        $pdo = getDB();
        $rows = $pdo->query("SELECT username, password_used, ip_address, login_time, login_status FROM login_logs ORDER BY login_time DESC")->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=log_login_' . date('Ymd_His') . '.csv');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Username', 'Password', 'IP Address', 'Waktu Login', 'Status']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['username'], $r['password_used'], $r['ip_address'], $r['login_time'], $r['login_status']]);
        }
        fclose($out);
        exit;
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
}

try {
    $pdo = getDB();
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    $total = $pdo->query("SELECT COUNT(*) FROM login_logs")->fetchColumn();
    $totalPages = ceil($total / $limit);
    $rows = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT $limit OFFSET $offset")->fetchAll();
} catch (Exception $e) {
    $rows = [];
    $total = 0;
    $totalPages = 0;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Login - Support Map</title>
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
            position: sticky; top: 0; z-index: 100;
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
        .header-right { display: flex; align-items: center; gap: 12px; }
        .btn-header {
            background: rgba(255,255,255,0.08); color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.18); padding: 6px 14px;
            border-radius: 8px; font-size: 13px; text-decoration: none; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-header:hover {
            background: rgba(14, 165, 233, 0.2);
            border-color: rgba(56, 189, 248, 0.4);
            color: white;
        }
        .btn-download {
            background: linear-gradient(135deg, #0284c7, #0ea5e9); color: white;
            border: none; padding: 7px 18px; border-radius: 8px;
            font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .btn-download:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5); }
        .container { padding: 24px; max-width: 1200px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; }
        .stat {
            background: rgba(11, 21, 45, 0.95); padding: 8px 16px; border-radius: 8px;
            font-size: 13px; font-weight: 600; color: #cbd5e1;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .stat span { color: #38bdf8; }
        .table-wrapper {
            background: rgba(11, 21, 45, 0.95); border-radius: 12px; overflow-x: auto;
            -webkit-overflow-scrolling: touch; box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 650px; }
        thead tr {
            background: linear-gradient(135deg, #0c1d3f, #132b5b); color: #38bdf8;
            border-bottom: 1px solid rgba(56, 189, 248, 0.25);
        }
        thead th { padding: 12px 14px; text-align: left; font-weight: 600; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #132347; transition: background 0.15s; }
        tbody tr:hover { background: rgba(14, 165, 233, 0.08); }
        tbody td { padding: 11px 14px; color: #cbd5e1; white-space: nowrap; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-failed { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 20px; }
        .page-link {
            padding: 7px 13px; border-radius: 8px; font-size: 13px; font-weight: 500;
            text-decoration: none; color: #cbd5e1; background: rgba(11, 21, 45, 0.95);
            border: 1px solid rgba(56, 189, 248, 0.2); transition: all 0.2s;
        }
        .page-link:hover, .page-link.active {
            background: linear-gradient(135deg, #0284c7, #0ea5e9); color: white; border-color: #0ea5e9;
        }
        .empty-msg { text-align: center; padding: 40px; color: #64748b; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #38bdf8; text-decoration: none; font-size: 14px; font-weight: 500;
            margin-bottom: 20px; transition: color 0.2s;
        }
        .back-link:hover { color: #7dd3fc; }

        @media (max-width: 768px) {
            .container { padding: 14px; }
            .header-brand { font-size: 16px; }
            .btn-header, .btn-download { padding: 4px 10px; font-size: 12px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .page-title::after { content: " (geser ↔)"; font-size: 12px; font-weight: normal; color: #38bdf8; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Log Login</span>
    </div>
    <div class="header-right">
        <a href="?download=1" class="btn-download">⬇️ Download CSV</a>
        <a href="functions.php" class="btn-header">← Kembali</a>
    </div>
</div>

<div class="container">
    <a href="functions.php" class="back-link">← Fungsi Lainnya</a>
    <div class="page-header">
        <div class="page-title">Riwayat Log Login</div>
        <div class="stat">Total: <span><?= number_format($total) ?></span> record</div>
    </div>

    <div class="table-wrapper">
        <?php if (empty($rows)): ?>
        <div class="empty-msg">Belum ada log login<?= isset($error) ? " — Error: $error" : "" ?></div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>IP Address</th>
                    <th>Waktu Login</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($r['username'] ?? '-') ?></strong></td>
                    <td><code><?= htmlspecialchars($r['password_used'] ?? '-') ?></code></td>
                    <td><?= htmlspecialchars($r['ip_address'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['login_time'] ?? '-') ?></td>
                    <td>
                        <span class="badge <?= $r['login_status'] === 'success' ? 'badge-success' : 'badge-failed' ?>">
                            <?= $r['login_status'] === 'success' ? '✅ Berhasil' : '❌ Gagal' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>" class="page-link">← Prev</a><?php endif; ?>
        <?php for ($p = max(1, $page-3); $p <= min($totalPages, $page+3); $p++): ?>
        <a href="?page=<?= $p ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>" class="page-link">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script src="assets/inactivity.js"></script>
<script src="assets/cookie_consent.js"></script>
</body>
</html>

