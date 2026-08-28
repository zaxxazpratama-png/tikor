<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

$success = '';
$error = '';
$importLog = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload gagal! Error code: ' . $file['error'];
    } elseif (!in_array(pathinfo($file['name'], PATHINFO_EXTENSION), ['xlsx', 'xls'])) {
        $error = 'File harus berformat .xlsx atau .xls!';
    } else {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $tmpPath = $uploadDir . 'import_' . time() . '_' . basename($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $tmpPath)) {
            // Process using Python (PhpSpreadsheet not available, use built-in ZIP-based reader)
            // Simple XLSX reader approach
            try {
                $result = processXLSX($tmpPath, 2); // Sheet index 2 (Sheet2)
                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    $imported = insertTikorData($result['rows']);
                    $success = "Berhasil import {$imported['success']} baris data! (Gagal: {$imported['fail']})";
                    $importLog = $imported['log'];
                }
            } catch (Exception $e) {
                $error = 'Error memproses file: ' . $e->getMessage();
            }
            @unlink($tmpPath);
        } else {
            $error = 'Gagal menyimpan file upload!';
        }
    }
}

function processXLSX($filePath, $sheetNumber = 2) {
    // Auto-detect Python binary
    $candidates = [
        'C:\\Users\\BLUE I.T COMPUTER\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
        'python3',
        'python',
        '/usr/bin/python3',
        '/usr/local/bin/python3'
    ];
    $pythonPath = 'python3';
    foreach ($candidates as $candidate) {
        if (file_exists($candidate) || (PHP_OS_FAMILY === 'Linux' && is_executable($candidate))) {
            $pythonPath = $candidate;
            break;
        }
    }
    
    $scriptPath = __DIR__ . '/api/read_xlsx.py';
    $cmd = escapeshellarg($pythonPath) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($filePath) . ' ' . (int)$sheetNumber . ' 2>&1';
    $output = shell_exec($cmd);
    
    if (!$output) return ['error' => 'Gagal membaca file Excel (Python tidak merespons)'];
    
    $data = json_decode($output, true);
    if (!$data) return ['error' => 'Gagal parse data: ' . substr($output, 0, 200)];
    if (isset($data['error'])) return ['error' => $data['error']];
    
    return $data;
}

function insertTikorData($rows) {
    $pdo = getDB();
    $success = 0;
    $fail = 0;
    $log = [];
    
    // Skip header row (index 0)
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        try {
            // Parse coordinates
            $lat = null; $lng = null;
            $coord = $row[9] ?? ''; // homepassed_koordinat
            if ($coord && $coord !== 'NULL') {
                $parts = explode(',', $coord);
                if (count($parts) >= 2) {
                    $lat = floatval(trim($parts[0]));
                    $lng = floatval(trim($parts[1]));
                }
            }
            
            $splitterLat = null; $splitterLng = null;
            $scoord = $row[17] ?? ''; // spliter_distribusi_koordinat
            if ($scoord && $scoord !== 'NULL') {
                $sparts = explode(',', $scoord);
                if (count($sparts) >= 2) {
                    $splitterLat = floatval(trim($sparts[0]));
                    $splitterLng = floatval(trim($sparts[1]));
                }
            }

            $stmt = $pdo->prepare("INSERT INTO tikor 
                (homepass_id, project_id, region, sub_region, provinsi, kota, kecamatan, kelurahan, kode_pos,
                 homepassed_koordinat, lat, lng, resident_type, resident_name, nama_jalan, no_rumah, unit,
                 pop_id, splitter_id, spliter_distribusi_koordinat, splitter_lat, splitter_lng,
                 remark, rfs_status, homepass_status, cluster_name, submission_date, last_update)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE lat=VALUES(lat), lng=VALUES(lng), homepass_status=VALUES(homepass_status)
            ");
            
            $nullify = function($v) { return ($v === 'NULL' || $v === null || $v === '') ? null : $v; };
            
            $stmt->execute([
                $nullify($row[0] ?? null),  // homepass_id
                $nullify($row[1] ?? null),  // project_id
                $nullify($row[2] ?? null),  // region
                $nullify($row[3] ?? null),  // sub_region
                $nullify($row[4] ?? null),  // provinsi
                $nullify($row[5] ?? null),  // kota
                $nullify($row[6] ?? null),  // kecamatan
                $nullify($row[7] ?? null),  // kelurahan
                $nullify($row[8] ?? null),  // kode_pos
                $nullify($coord),           // homepassed_koordinat
                $lat, $lng,
                $nullify($row[10] ?? null), // resident_type
                $nullify($row[11] ?? null), // resident_name
                $nullify($row[12] ?? null), // nama_jalan
                $nullify($row[13] ?? null), // no_rumah
                $nullify($row[14] ?? null), // unit
                $nullify($row[15] ?? null), // pop_id
                $nullify($row[16] ?? null), // splitter_id
                $nullify($scoord),          // spliter_distribusi_koordinat
                $splitterLat, $splitterLng,
                $nullify($row[18] ?? null), // remark
                $nullify($row[21] ?? null), // rfs_status
                $nullify($row[41] ?? null), // homepass_status
                $nullify($row[39] ?? null), // cluster_name
                $nullify($row[22] ?? null), // submission_date
                $nullify($row[24] ?? null), // last_update
            ]);
            $success++;
        } catch (Exception $e) {
            $fail++;
            if (count($log) < 5) $log[] = "Row $i: " . $e->getMessage();
        }
    }
    return ['success' => $success, 'fail' => $fail, 'log' => $log];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - Support Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo-tin.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #c2410c, #f97316);
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
        .container { max-width: 640px; margin: 50px auto; padding: 0 20px; }
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #f97316; text-decoration: none; font-size: 14px; font-weight: 500;
            margin-bottom: 24px;
        }
        .card {
            background: white; border-radius: 16px; padding: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .card-icon { font-size: 48px; text-align: center; margin-bottom: 16px; }
        .card-title { font-size: 22px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 8px; }
        .card-subtitle { color: #6b7280; text-align: center; font-size: 14px; margin-bottom: 32px; line-height: 1.6; }
        .upload-zone {
            border: 2px dashed #f97316;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: #fff7ed;
            margin-bottom: 24px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .upload-zone:hover { background: #ffedd5; }
        .upload-zone .icon { font-size: 48px; margin-bottom: 12px; }
        .upload-zone p { color: #6b7280; font-size: 14px; }
        .upload-zone strong { color: #ea580c; }
        #file-input { display: none; }
        .file-name {
            background: #f3f4f6; border-radius: 8px; padding: 10px 14px;
            font-size: 14px; color: #374151; margin-bottom: 20px;
            display: none;
        }
        .info-box {
            background: #eff6ff; border-radius: 10px; padding: 16px;
            margin-bottom: 24px; font-size: 13px; color: #1e3a8a; line-height: 1.7;
        }
        .info-box strong { display: block; margin-bottom: 6px; font-size: 14px; }
        .btn-submit {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #c2410c, #f97316);
            color: white; border: none; border-radius: 10px; font-size: 16px;
            font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;
            transition: transform 0.1s, opacity 0.2s;
        }
        .btn-submit:hover { transform: translateY(-1px); opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .log-list { font-size: 12px; color: #ef4444; margin-top: 8px; }
        .progress-bar {
            display: none; height: 6px; background: #e5e7eb; border-radius: 3px;
            margin-bottom: 16px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; background: linear-gradient(90deg, #c2410c, #f97316);
            border-radius: 3px; animation: progress 2s ease-in-out infinite;
        }
        @keyframes progress {
            0% { width: 10%; }
            50% { width: 80%; }
            100% { width: 95%; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Import Data TIKOR</span>
    </div>
    <a href="functions.php" class="btn-header">← Kembali</a>
</div>

<div class="container">
    <a href="functions.php" class="back-link">← Fungsi Lainnya</a>
    <div class="card">
        <div class="card-icon">📥</div>
        <div class="card-title">Import Data TIKOR</div>
        <div class="card-subtitle">Upload file Excel (.xlsx) dari CBN/Biznet.<br>Data akan diambil dari <strong>Sheet2</strong>.</div>

        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php if ($importLog): ?>
        <div class="log-list">
            <strong>Error log:</strong><br>
            <?php foreach ($importLog as $l): ?>
            <?= htmlspecialchars($l) ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ Panduan Import:</strong>
            • File harus format <strong>.xlsx</strong><br>
            • Data tikor ada di <strong>Sheet2</strong><br>
            • Kolom yang dibutuhkan: homepass_id, homepassed_koordinat, dll<br>
            • Data duplikat (homepass_id sama) akan diperbarui otomatis<br>
            • Proses import mungkin memakan waktu beberapa menit
        </div>

        <form method="POST" enctype="multipart/form-data" id="importForm">
            <div class="upload-zone" onclick="document.getElementById('file-input').click()">
                <div class="icon">📄</div>
                <p>Klik atau drag & drop file Excel di sini</p>
                <p><strong>Format: .xlsx | Maks: 50MB</strong></p>
            </div>
            <input type="file" name="excel_file" id="file-input" accept=".xlsx,.xls" 
                   onchange="handleFileSelect(this)">
            <div class="file-name" id="file-name">📄 <span></span></div>
            <div class="progress-bar" id="progress-bar"><div class="progress-fill"></div></div>
            <button type="submit" class="btn-submit" id="btn-submit" disabled onclick="showProgress()">
                📥 Mulai Import Data
            </button>
        </form>
    </div>
</div>

<script>
function handleFileSelect(input) {
    var fileName = input.files[0]?.name;
    if (fileName) {
        document.getElementById('file-name').style.display = 'block';
        document.getElementById('file-name').querySelector('span').textContent = fileName;
        document.getElementById('btn-submit').disabled = false;
    }
}

function showProgress() {
    document.getElementById('progress-bar').style.display = 'block';
    document.getElementById('btn-submit').disabled = true;
    document.getElementById('btn-submit').textContent = '⏳ Sedang mengimport...';
}

// Drag and drop
var zone = document.querySelector('.upload-zone');
zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.style.background = '#ffedd5'; });
zone.addEventListener('dragleave', function() { zone.style.background = '#fff7ed'; });
zone.addEventListener('drop', function(e) {
    e.preventDefault();
    zone.style.background = '#fff7ed';
    var file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('file-input').files = e.dataTransfer.files;
        handleFileSelect(document.getElementById('file-input'));
    }
});
</script>
<script src="assets/inactivity.js"></script>
</body>
</html>

