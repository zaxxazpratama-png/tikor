<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$filterKota = trim($_GET['kota'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

try {
    $pdo = getDB();
    $where = [];
    $params = [];
    if ($search) {
        $where[] = "(homepass_id LIKE ? OR nama_jalan LIKE ? OR resident_name LIKE ? OR kelurahan LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
    }
    if ($filterKota) { $where[] = "kota = ?"; $params[] = $filterKota; }
    if ($filterStatus) { $where[] = "homepass_status = ?"; $params[] = $filterStatus; }

    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tikor $whereStr");
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    $stmt = $pdo->prepare("SELECT * FROM tikor $whereStr ORDER BY id ASC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Get unique cities and statuses for filters
    $cities = $pdo->query("SELECT DISTINCT kota FROM tikor WHERE kota IS NOT NULL ORDER BY kota")->fetchAll(PDO::FETCH_COLUMN);
    $statuses = $pdo->query("SELECT DISTINCT homepass_status FROM tikor WHERE homepass_status IS NOT NULL ORDER BY homepass_status")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $rows = [];
    $totalRows = 0;
    $totalPages = 0;
    $cities = [];
    $statuses = [];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Data - Support Map</title>
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
        .container { padding: 24px; max-width: 1400px; margin: 0 auto; }
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 20px; }
        .filter-bar {
            background: rgba(11, 21, 45, 0.95); padding: 16px 20px; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4); margin-bottom: 20px;
            display: flex; align-items: center; flex-wrap: wrap; gap: 12px;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .filter-bar input, .filter-bar select {
            padding: 9px 14px; background: #0f1c3a;
            border: 1.5px solid #1c325c; border-radius: 8px;
            font-size: 14px; font-family: 'Inter', sans-serif; color: #f8fafc;
            outline: none; transition: border-color 0.2s;
        }
        .filter-bar input::placeholder { color: #64748b; }
        .filter-bar input:focus, .filter-bar select:focus {
            border-color: #0ea5e9; background: #132347;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
        }
        .filter-bar input[type="text"] { width: 280px; }
        .btn-filter {
            padding: 9px 18px; background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white; border: none; border-radius: 8px; font-size: 14px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
        }
        .btn-reset-filter {
            padding: 9px 14px; background: rgba(255,255,255,0.06); color: #cbd5e1;
            border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; font-size: 14px;
            cursor: pointer; font-family: 'Inter', sans-serif; text-decoration: none;
        }
        .stats-bar {
            display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
        }
        .stat-badge {
            background: rgba(11, 21, 45, 0.95); padding: 8px 16px; border-radius: 8px;
            font-size: 13px; font-weight: 600; color: #cbd5e1;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .stat-badge span { color: #38bdf8; }
        .table-wrapper {
            background: rgba(11, 21, 45, 0.95); border-radius: 12px; overflow-x: auto;
            -webkit-overflow-scrolling: touch; box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 900px; }
        thead tr {
            background: linear-gradient(135deg, #0c1d3f, #132b5b); color: #38bdf8;
            border-bottom: 1px solid rgba(56, 189, 248, 0.25);
        }
        thead th { padding: 12px 10px; text-align: left; font-weight: 600; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #132347; transition: background 0.15s; }
        tbody tr:hover { background: rgba(14, 165, 233, 0.08); }
        tbody td { padding: 10px 10px; color: #cbd5e1; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .badge-idle { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-default { background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); }
        .pagination {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-top: 20px; flex-wrap: wrap;
        }
        .page-link {
            padding: 7px 13px; border-radius: 8px; font-size: 13px; font-weight: 500;
            text-decoration: none; color: #cbd5e1; background: rgba(11, 21, 45, 0.95);
            border: 1px solid rgba(56, 189, 248, 0.2); transition: all 0.2s;
        }
        .page-link:hover, .page-link.active {
            background: linear-gradient(135deg, #0284c7, #0ea5e9); color: white; border-color: #0ea5e9;
        }
        .empty-msg { text-align: center; padding: 40px; color: #64748b; font-size: 15px; }

        @media (max-width: 768px) {
            .container { padding: 14px; }
            .header-brand { font-size: 16px; }
            .btn-header { padding: 4px 10px; font-size: 12px; }
            .filter-bar input[type="text"], .filter-bar select { width: 100%; }
            .btn-filter, .btn-reset-filter { width: 100%; text-align: center; }
            .page-title::after { content: " (geser ↔)"; font-size: 12px; font-weight: normal; color: #38bdf8; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Lihat Data TIKOR</span>
    </div>
    <a href="functions.php" class="btn-header">← Kembali</a>
</div>
<div class="container">
    <div class="page-title">Data TIKOR Database</div>
    
    <form class="filter-bar" method="GET">
        <input type="text" name="search" placeholder="🔍 Cari HomepassID, Nama Jalan, Klaster..." 
               value="<?= htmlspecialchars($search) ?>">
        <select name="kota">
            <option value="">Semua Kota</option>
            <?php foreach ($cities as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $filterKota === $c ? 'selected' : '' ?>>
                <?= htmlspecialchars($c) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Semua Status</option>
            <?php foreach ($statuses as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>>
                <?= htmlspecialchars($s) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-filter">Filter</button>
        <a href="view_data.php" class="btn-reset-filter">Reset</a>
    </form>

    <div class="stats-bar">
        <div class="stat-badge">Total: <span><?= number_format($totalRows) ?></span> baris</div>
        <div class="stat-badge">Halaman: <span><?= $page ?> / <?= max(1, $totalPages) ?></span></div>
        <?php if ($search || $filterKota || $filterStatus): ?>
        <div class="stat-badge">🔍 Filter aktif</div>
        <?php endif; ?>
    </div>

    <div class="table-wrapper">
        <?php if (empty($rows)): ?>
        <div class="empty-msg">Tidak ada data<?= isset($error) ? " - Error: $error" : "" ?></div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>HomepassID</th>
                    <th>Kode Pos</th>
                    <th>No Rumah</th>
                    <th>Nama Jalan</th>
                    <th>Kelurahan</th>
                    <th>Kecamatan</th>
                    <th>Kota</th>
                    <th>Tipe Rumah</th>
                    <th>Koordinat (Homepass)</th>
                    <th>Nama Klaster</th>
                    <th>Status</th>
                    <th>Pop ID</th>
                    <th>Splitter ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $r): 
                    $statusClass = strtolower($r['homepass_status'] ?? '') === 'idle' ? 'badge-idle' :
                                   (strtolower($r['homepass_status'] ?? '') === 'active' ? 'badge-active' : 'badge-default');
                ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td title="<?= htmlspecialchars($r['homepass_id'] ?? '') ?>"><?= htmlspecialchars($r['homepass_id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['kode_pos'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['no_rumah'] ?? '-') ?></td>
                    <td title="<?= htmlspecialchars($r['nama_jalan'] ?? '') ?>"><?= htmlspecialchars($r['nama_jalan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['kelurahan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['kecamatan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['kota'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['resident_type'] ?? '-') ?></td>
                    <td><small><?= htmlspecialchars($r['homepassed_koordinat'] ?? '-') ?></small></td>
                    <td title="<?= htmlspecialchars($r['cluster_name'] ?? '') ?>"><?= htmlspecialchars($r['cluster_name'] ?? '-') ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($r['homepass_status'] ?? '-') ?></span></td>
                    <td><?= htmlspecialchars($r['pop_id'] ?? '-') ?></td>
                    <td title="<?= htmlspecialchars($r['splitter_id'] ?? '') ?>"><?= htmlspecialchars($r['splitter_id'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&kota=<?= urlencode($filterKota) ?>&status=<?= urlencode($filterStatus) ?>" class="page-link">← Prev</a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($p = $start; $p <= $end; $p++):
        ?>
        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&kota=<?= urlencode($filterKota) ?>&status=<?= urlencode($filterStatus) ?>" 
           class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&kota=<?= urlencode($filterKota) ?>&status=<?= urlencode($filterStatus) ?>" class="page-link">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script src="assets/inactivity.js"></script>
<script src="assets/cookie_consent.js"></script>
</body>
</html>

