<?php
require_once 'config/db.php';
checkLogin();
checkAdmin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $targetUserId = intval($_POST['user_id'] ?? 0);
    $deviceId     = intval($_POST['device_id'] ?? 0);

    try {
        $pdo = getDB();

        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->execute([$targetUserId]);
        $tUser = $uStmt->fetch();
        $tName = $tUser['username'] ?? '?';

        if ($action === 'allow_one_more' && $targetUserId > 0) {
            $pdo->prepare("UPDATE users SET max_devices = max_devices + 1 WHERE id = ?")->execute([$targetUserId]);
            $stmt = $pdo->prepare("SELECT max_devices FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $nm = $stmt->fetch()['max_devices'];
            $success = "User <strong>$tName</strong> sekarang diizinkan <strong>$nm perangkat</strong>.";

        } elseif ($action === 'set_max' && $targetUserId > 0) {
            $max = max(1, min(20, intval($_POST['max_devices'] ?? 1)));
            $pdo->prepare("UPDATE users SET max_devices = ? WHERE id = ?")->execute([$max, $targetUserId]);
            $success = "Batas device <strong>$tName</strong> diubah ke <strong>$max perangkat</strong>.";

        } elseif ($action === 'remove_device' && $deviceId > 0) {
            $d = $pdo->prepare("SELECT device_name FROM registered_devices WHERE id = ? AND user_id = ?");
            $d->execute([$deviceId, $targetUserId]);
            $dev = $d->fetch();
            if ($dev) {
                $pdo->prepare("DELETE FROM registered_devices WHERE id = ?")->execute([$deviceId]);
                $success = "Perangkat <strong>" . htmlspecialchars($dev['device_name']) . "</strong> milik <strong>$tName</strong> berhasil dihapus.";
            } else {
                $error = "Perangkat tidak ditemukan.";
            }

        } elseif ($action === 'clear_all' && $targetUserId > 0) {
            $pdo->prepare("DELETE FROM registered_devices WHERE user_id = ?")->execute([$targetUserId]);
            $pdo->prepare("DELETE FROM active_sessions WHERE user_id = ?")->execute([$targetUserId]);
            $success = "Semua perangkat & sesi <strong>$tName</strong> berhasil dihapus. User harus login ulang.";

        } elseif ($action === 'force_logout' && $targetUserId > 0) {
            $pdo->prepare("DELETE FROM active_sessions WHERE user_id = ?")->execute([$targetUserId]);
            $success = "Sesi aktif <strong>$tName</strong> berhasil dihentikan.";
        }

    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

cleanExpiredSessions();

try {
    $pdo = getDB();
    $users = $pdo->query("
        SELECT u.id, u.username, u.role, u.max_devices,
            (SELECT COUNT(*) FROM registered_devices r WHERE r.user_id = u.id) as reg_count,
            (SELECT COUNT(*) FROM active_sessions s WHERE s.user_id = u.id
             AND s.last_activity > DATE_SUB(NOW(), INTERVAL " . SESSION_TIMEOUT . " SECOND)) as sess_count
        FROM users u ORDER BY u.username ASC
    ")->fetchAll();

    $devices = $pdo->query("
        SELECT rd.*, u.username, u.id as uid
        FROM registered_devices rd
        JOIN users u ON u.id = rd.user_id
        ORDER BY u.username ASC, rd.registered_at ASC
    ")->fetchAll();

    $sessions = $pdo->query("
        SELECT s.*, u.username
        FROM active_sessions s JOIN users u ON u.id = s.user_id
        WHERE s.last_activity > DATE_SUB(NOW(), INTERVAL " . SESSION_TIMEOUT . " SECOND)
        ORDER BY s.last_activity DESC
    ")->fetchAll();

} catch (Exception $e) {
    $users = $devices = $sessions = [];
    $error = $e->getMessage();
}

function fmtTime($dt) {
    if (!$dt) return '-';
    $diff = (new DateTime())->diff(new DateTime($dt));
    if ($diff->i < 1 && $diff->h < 1 && $diff->days < 1) return 'baru saja';
    if ($diff->h < 1) return $diff->i . ' mnt lalu';
    if ($diff->days < 1) return $diff->h . ' jam lalu';
    return $diff->days . ' hari lalu';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Device - Support Map</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/logo-tin.png">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif;
            background: linear-gradient(180deg, #070e22 0%, #030712 100%);
            min-height: 100vh;
            color: #f1f5f9;
        }
        .header {
            background: linear-gradient(135deg, #040817 0%, #0b1836 100%);
            color:white; padding:0 20px; height:56px;
            display:flex; align-items:center; justify-content:space-between;
            position:sticky; top:0; z-index:200;
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .header-brand {
            font-size:18px; font-weight:700;
            display:flex; align-items:center; gap:10px;
            color: #f8fafc;
        }
        .header-logo {
            height:32px; width:auto; object-fit:contain;
            background: rgba(255, 255, 255, 0.95); padding:3px 8px; border-radius:6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .btn-hdr {
            background:rgba(255,255,255,0.08); color:#e2e8f0;
            border:1px solid rgba(255,255,255,0.18); padding:6px 14px;
            border-radius:8px; font-size:13px; text-decoration:none;
            transition: all 0.2s;
        }
        .btn-hdr:hover {
            background: rgba(14, 165, 233, 0.2);
            border-color: rgba(56, 189, 248, 0.4);
            color: white;
        }
        .wrap { max-width:1300px; margin:0 auto; padding:26px 20px; }
        .back {
            color:#38bdf8; text-decoration:none; font-size:14px; font-weight:500;
            display:inline-flex; align-items:center; gap:6px; margin-bottom:18px;
            transition: color 0.2s;
        }
        .back:hover { color: #7dd3fc; }
        h1 { font-size:22px; font-weight:700; color:#f8fafc; margin-bottom:4px; }
        .sub { color:#94a3b8; font-size:13px; margin-bottom:22px; }
        .alert { padding:13px 16px; border-radius:10px; margin-bottom:18px; font-size:14px; }
        .ok  { background:rgba(16, 185, 129, 0.15); color:#34d399; border:1px solid rgba(16, 185, 129, 0.3); }
        .err { background:rgba(239, 68, 68, 0.15); color:#fca5a5; border:1px solid rgba(239, 68, 68, 0.3); }

        /* Stats */
        .stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:26px; }
        .sc {
            background: rgba(11, 21, 45, 0.95); border-radius:12px; padding:16px 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3); flex:1; min-width:140px;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .sc .n { font-size:26px; font-weight:800; color:#38bdf8; }
        .sc .l { font-size:12px; color:#94a3b8; margin-top:3px; }

        /* Section */
        .sec-title { font-size:15px; font-weight:700; color:#f8fafc; margin:0 0 12px; }
        .card { 
            background: rgba(11, 21, 45, 0.95); 
            border-radius:14px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.45); 
            margin-bottom:26px; 
            overflow-x:auto; 
            -webkit-overflow-scrolling:touch; 
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        table { width:100%; border-collapse:collapse; font-size:13px; min-width:760px; }
        thead tr {
            background: linear-gradient(135deg, #0c1d3f, #132b5b); color:#38bdf8;
            border-bottom: 1px solid rgba(56, 189, 248, 0.25);
        }
        thead th { padding:12px 14px; text-align:left; font-weight:600; white-space:nowrap; }
        tbody tr { border-bottom:1px solid #132347; transition:background .15s; }
        tbody tr:hover { background: rgba(14, 165, 233, 0.08); }
        tbody td { padding:11px 14px; color:#cbd5e1; vertical-align:middle; white-space:nowrap; }
        .badge { display:inline-block; padding:2px 9px; border-radius:12px; font-size:11px; font-weight:700; }
        .b-green { background:rgba(16, 185, 129, 0.15); color:#34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .b-red   { background:rgba(239, 68, 68, 0.15); color:#fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .b-gray  { background:rgba(148, 163, 184, 0.15); color:#94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); }
        .b-yel   { background:rgba(245, 158, 11, 0.15); color:#fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .b-blue  { background:rgba(14, 165, 233, 0.15); color:#38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .acts { display:flex; gap:6px; flex-wrap:wrap; }
        .btn { padding:5px 11px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; border:none; font-family:'Inter',sans-serif; transition:opacity .2s; white-space:nowrap; }
        .btn:hover { opacity:.85; }
        .bn-green  { background:#10b981; color:white; }
        .bn-red    { background:#ef4444; color:white; }
        .bn-orange { background:#f97316; color:white; }
        .bn-purple { background:#0ea5e9; color:white; }
        .mn-form { display:flex; align-items:center; gap:6px; }
        .mn-inp {
            width:52px; padding:5px 8px; background:#0f1c3a;
            border:1.5px solid #1c325c; border-radius:6px; font-size:13px;
            text-align:center; font-family:'Inter',sans-serif; color:#f8fafc;
        }
        .mn-inp:focus { border-color:#0ea5e9; outline:none; }
        .ua-s { font-size:11px; color:#64748b; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .empty { text-align:center; padding:36px; color:#64748b; }
        .slot-ok   { color:#34d399; font-weight:700; }
        .slot-full { color:#f87171; font-weight:700; }

        /* ── Modal ────────────────────────────────── */
        .modal-backdrop {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.7); z-index:500;
            align-items:center; justify-content:center;
            backdrop-filter: blur(4px);
        }
        .modal-backdrop.show { display:flex; }
        .modal {
            background: #0b152d; border-radius:16px; padding:32px 28px;
            max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.6);
            border: 1px solid rgba(56, 189, 248, 0.25);
            animation:mIn .2s ease;
        }
        @keyframes mIn { from { transform:scale(.9); opacity:0; } to { transform:scale(1); opacity:1; } }
        .modal-icon { font-size:40px; text-align:center; margin-bottom:12px; }
        .modal-title { font-size:18px; font-weight:700; text-align:center; margin-bottom:8px; color:#f8fafc; }
        .modal-msg { font-size:14px; text-align:center; color:#94a3b8; margin-bottom:24px; line-height:1.6; }
        .modal-btns { display:flex; gap:10px; justify-content:center; }
        .modal-btns .btn { padding:10px 24px; font-size:14px; border-radius:8px; }

        @media (max-width: 768px) {
            .wrap { padding: 16px 12px; }
            .stats { gap: 8px; margin-bottom: 20px; }
            .sc { min-width: calc(50% - 4px); padding: 12px 14px; }
            .sc .n { font-size: 22px; }
            .btn-hdr { padding: 4px 10px; font-size: 12px; }
            .sec-title { font-size: 14px; }
            .sec-title::after { content: " (geser ↔)"; font-size: 11px; font-weight: normal; color: #38bdf8; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>Kelola Perangkat</span>
    </div>
    <a href="functions.php" class="btn-hdr">← Kembali</a>
</div>

<!-- Custom Confirm Modal -->
<div class="modal-backdrop" id="confirmModal">
    <div class="modal">
        <div class="modal-icon" id="mIcon">⚠️</div>
        <div class="modal-title" id="mTitle">Konfirmasi</div>
        <div class="modal-msg" id="mMsg">Apakah Anda yakin?</div>
        <div class="modal-btns">
            <button class="btn bn-red" id="mConfirm">Ya, Lanjutkan</button>
            <button class="btn" style="background:#f3f4f6;color:#374151;" onclick="closeModal()">Batal</button>
        </div>
    </div>
</div>

<div class="wrap">
    <a href="functions.php" class="back">← Fungsi Lainnya</a>
    <h1>Kelola Perangkat Pengguna</h1>
    <p class="sub">Perangkat terdaftar <strong>tetap tersimpan meski user logout</strong>. Hapus perangkat agar user tidak bisa login dari sini lagi.</p>

    <?php if ($success): ?><div class="alert ok">✅ <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="sc"><div class="n"><?= count($users) ?></div><div class="l">Total User</div></div>
        <div class="sc"><div class="n"><?= count($devices) ?></div><div class="l">Perangkat Terdaftar</div></div>
        <div class="sc"><div class="n"><?= count($sessions) ?></div><div class="l">Sesi Aktif</div></div>
        <div class="sc"><div class="n"><?= count(array_filter($users, fn($u) => $u['reg_count'] >= $u['max_devices'])) ?></div><div class="l">User Slot Penuh</div></div>
    </div>

    <!-- ═══ USER SETTINGS ═══════════════════════════════════════════════ -->
    <div class="sec-title">👥 Pengaturan Akses per User</div>
    <div class="card">
        <table>
            <thead><tr>
                <th>Username</th><th>Role</th><th>Status</th>
                <th>Perangkat</th><th>Ubah Batas</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $u):
                $full = $u['reg_count'] >= $u['max_devices'];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td><span class="badge <?= $u['role']==='admin'?'b-yel':'b-blue' ?>"><?= $u['role']==='admin'?'👑 Admin':'👤 User' ?></span></td>
                <td><span class="badge <?= $u['sess_count']>0?'b-green':'b-gray' ?>"><?= $u['sess_count']>0?'🟢 Online':'⚫ Offline' ?></span></td>
                <td>
                    <span class="<?= $full?'slot-full':'slot-ok' ?>">🖥️ <?= $u['reg_count'] ?>/<?= $u['max_devices'] ?></span>
                    <span class="badge <?= $full?'b-red':'b-green' ?>" style="margin-left:6px"><?= $full?'PENUH':'Ada Slot' ?></span>
                </td>
                <td>
                    <form method="POST" class="mn-form">
                        <input type="hidden" name="action" value="set_max">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="number" name="max_devices" class="mn-inp" value="<?= $u['max_devices'] ?>" min="1" max="20">
                        <button type="submit" class="btn bn-purple">Simpan</button>
                    </form>
                </td>
                <td>
                    <div class="acts">
                        <!-- +1 Device -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="allow_one_more">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn bn-green">➕ +1 Device</button>
                        </form>
                        <!-- Force Logout -->
                        <?php if ($u['sess_count'] > 0 && $u['id'] != $_SESSION['user_id']): ?>
                        <button class="btn bn-orange"
                            onclick="showModal(
                                '⏏️','Force Logout',
                                'Putus sesi aktif <b><?= htmlspecialchars($u['username']) ?></b>?<br>Perangkat tetap terdaftar.',
                                'force_logout', <?= $u['id'] ?>, 0
                            )">⏏️ Force Logout</button>
                        <?php endif; ?>
                        <!-- Clear All -->
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <button class="btn bn-red"
                            onclick="showModal(
                                '🗑️','Reset Semua Perangkat',
                                'Hapus SEMUA perangkat &amp; sesi <b><?= htmlspecialchars($u['username']) ?></b>?<br>User harus login ulang dari awal!',
                                'clear_all', <?= $u['id'] ?>, 0
                            )">🗑️ Reset Semua</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══ REGISTERED DEVICES ══════════════════════════════════════════ -->
    <div class="sec-title">📱 Perangkat Terdaftar (<?= count($devices) ?>)</div>
    <div class="card">
        <?php if (empty($devices)): ?>
        <div class="empty">Belum ada perangkat terdaftar.<br>Perangkat akan muncul saat user pertama kali login.</div>
        <?php else: ?>
        <table>
            <thead><tr>
                <th>Username</th><th>Nama Perangkat</th><th>IP Address</th>
                <th>Pertama Login</th><th>Terakhir Aktif</th><th>Hapus</th>
            </tr></thead>
            <tbody>
            <?php foreach ($devices as $d): ?>
            <tr>
                <td><strong><?= htmlspecialchars($d['username']) ?></strong></td>
                <td>
                    🖥️ <?= htmlspecialchars($d['device_name'] ?? 'Unknown') ?>
                    <div class="ua-s" title="<?= htmlspecialchars($d['user_agent']??'') ?>"><?= htmlspecialchars(substr($d['user_agent']??'',0,70)) ?></div>
                </td>
                <td><?= htmlspecialchars($d['ip_address']??'-') ?></td>
                <td><?= !empty($d['registered_at']) ? date('d/m/Y H:i', strtotime($d['registered_at'])) : '-' ?></td>
                <td>
                    <?= !empty($d['last_seen']) ? date('d/m/Y H:i', strtotime($d['last_seen'])) : '-' ?>
                    <div style="font-size:11px;color:#9ca3af"><?= fmtTime($d['last_seen'] ?? null) ?></div>
                </td>
                <td>
                    <button class="btn bn-red"
                        onclick="showModal(
                            '🗑️','Hapus Perangkat',
                            'Hapus perangkat <b><?= htmlspecialchars($d['device_name']??'ini') ?></b> milik <b><?= htmlspecialchars($d['username']) ?></b>?<br>User tidak bisa login dari perangkat ini lagi.',
                            'remove_device', <?= $d['uid'] ?>, <?= $d['id'] ?>
                        )">🗑️ Hapus</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ═══ ACTIVE SESSIONS ══════════════════════════════════════════════ -->
    <div class="sec-title">📡 Sesi Aktif (<?= count($sessions) ?>)</div>
    <div class="card">
        <?php if (empty($sessions)): ?>
        <div class="empty">Tidak ada sesi aktif saat ini</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Username</th><th>IP</th><th>Terakhir Aktif</th><th>Waktu Sesi</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['username']) ?></strong></td>
                <td><?= htmlspecialchars($s['ip_address']??'-') ?></td>
                <td>
                    <?= date('H:i:s', strtotime($s['last_activity'])) ?>
                    <div style="font-size:11px;color:#9ca3af"><?= fmtTime($s['last_activity']) ?></div>
                </td>
                <td><?= !empty($s['created_at']) ? date('d/m/Y H:i', strtotime($s['created_at'])) : date('d/m/Y H:i', strtotime($s['last_activity'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden form for modal actions -->
<form method="POST" id="actionForm" style="display:none">
    <input type="hidden" name="action"      id="f_action">
    <input type="hidden" name="user_id"     id="f_uid">
    <input type="hidden" name="device_id"   id="f_did">
</form>

<script>
function showModal(icon, title, msg, action, uid, did) {
    document.getElementById('mIcon').textContent = icon;
    document.getElementById('mTitle').textContent = title;
    document.getElementById('mMsg').innerHTML = msg;
    document.getElementById('mConfirm').onclick = function() {
        document.getElementById('f_action').value = action;
        document.getElementById('f_uid').value    = uid;
        document.getElementById('f_did').value    = did;
        document.getElementById('actionForm').submit();
    };
    document.getElementById('confirmModal').classList.add('show');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('show');
}

// Close modal on backdrop click
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
<script src="assets/inactivity.js"></script>
</body>
</html>

