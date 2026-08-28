<?php
require_once 'config/db.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUPPORT MAP - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
            padding: 0 20px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .header-user {
            background: rgba(255,255,255,0.15);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .btn-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-header:hover { background: rgba(255,255,255,0.35); }

        /* Controls */
        .controls-bar {
            background: white;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .controls-bar label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }
        .coord-input {
            padding: 8px 14px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            width: 240px;
            outline: none;
            transition: border-color 0.2s;
        }
        .coord-input:focus { border-color: #40916c; }
        .radius-select {
            padding: 8px 12px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .radius-select:focus { border-color: #40916c; }
        .btn-search {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
            border: none;
            padding: 9px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: transform 0.1s, box-shadow 0.2s;
        }
        .btn-search:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(64,145,108,0.4);
        }
        .btn-reset {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        .btn-reset:hover { background: #e5e7eb; }

        /* Map */
        #map {
            height: 420px;
            width: 100%;
            z-index: 1;
        }

        /* Table area */
        .table-section {
            padding: 20px;
        }
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .table-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .result-count {
            font-size: 13px;
            color: #6b7280;
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .tbl thead tr {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
        }
        .tbl thead th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            white-space: nowrap;
        }
        .tbl tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s;
        }
        .tbl tbody tr:hover { background: #f0fdf4; }
        .tbl tbody td {
            padding: 10px 10px;
            color: #374151;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-idle { background: #fef3c7; color: #d97706; }
        .badge-active { background: #d1fae5; color: #059669; }
        .badge-default { background: #f3f4f6; color: #6b7280; }
        .btn-copy {
            background: linear-gradient(135deg, #2d6a4f, #40916c);
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: opacity 0.2s;
        }
        .btn-copy:hover { opacity: 0.85; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 15px; }
        .loading-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .spinner {
            display: inline-block;
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top-color: #40916c;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1a1a2e;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-brand">
        🗺️ SUPPORT MAP
    </div>
    <div class="header-right">
        <span class="header-user">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="functions.php" class="btn-header">⚙️ Setting</a>
        <a href="logout.php" class="btn-header">🚪 Logout</a>
    </div>
</div>

<!-- Controls -->
<div class="controls-bar">
    <label>Enter Coordinates:</label>
    <input type="text" id="coord-input" class="coord-input" 
           placeholder="latitude,longitude (contoh: -6.164370, 107.158614)"
           title="Format: latitude,longitude">
    <label>Radius:</label>
    <select id="radius-select" class="radius-select">
        <option value="10">10 meter</option>
        <option value="20">20 meter</option>
        <option value="30" selected>30 meter</option>
        <option value="50">50 meter</option>
        <option value="100">100 meter</option>
        <option value="200">200 meter</option>
        <option value="500">500 meter</option>
    </select>
    <button class="btn-search" onclick="searchNearby()" id="btn-find">🔍 Find Nearby Points</button>
    <button class="btn-reset" onclick="resetMap()">↺ Reset</button>
</div>

<!-- Map -->
<div id="map"></div>

<!-- Table -->
<div class="table-section">
    <div class="table-header">
        <div class="table-title">📋 Hasil Pencarian Tikor</div>
        <div class="result-count" id="result-count">Belum ada pencarian</div>
    </div>
    <div class="table-wrapper">
        <div id="table-content">
            <div class="empty-state">
                <div class="icon">🔍</div>
                <p>Masukkan koordinat dan klik <strong>Find Nearby Points</strong> untuk mencari tikor terdekat</p>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Init Map
var map = L.map('map').setView([-6.2088, 106.8456], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);

var markers = [];
var centerMarker = null;
var radiusCircle = null;

// Custom icons
var tikorIcon = L.divIcon({
    html: '<div style="background:#40916c;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3)"></div>',
    iconSize: [14, 14],
    iconAnchor: [7, 7],
    className: ''
});

var centerIcon = L.divIcon({
    html: '<div style="background:#dc2626;width:20px;height:20px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.4)"></div>',
    iconSize: [20, 20],
    iconAnchor: [10, 10],
    className: ''
});

// Click on map to get coordinates
map.on('click', function(e) {
    var lat = e.latlng.lat.toFixed(8);
    var lng = e.latlng.lng.toFixed(8);
    document.getElementById('coord-input').value = lat + ', ' + lng;
    showToast('📍 Koordinat dipilih: ' + lat + ', ' + lng);
});

function clearMarkers() {
    markers.forEach(m => map.removeLayer(m));
    markers = [];
    if (centerMarker) { map.removeLayer(centerMarker); centerMarker = null; }
    if (radiusCircle) { map.removeLayer(radiusCircle); radiusCircle = null; }
}

function searchNearby() {
    var coordVal = document.getElementById('coord-input').value.trim();
    var radius = document.getElementById('radius-select').value;

    if (!coordVal) {
        showToast('⚠️ Masukkan koordinat terlebih dahulu!');
        return;
    }

    // Parse coordinates
    var parts = coordVal.split(',');
    if (parts.length < 2) {
        showToast('⚠️ Format koordinat: latitude, longitude');
        return;
    }
    var lat = parseFloat(parts[0].trim());
    var lng = parseFloat(parts[1].trim());

    if (isNaN(lat) || isNaN(lng)) {
        showToast('⚠️ Koordinat tidak valid!');
        return;
    }

    clearMarkers();

    // Show loading
    document.getElementById('table-content').innerHTML = '<div class="loading-state"><div class="spinner"></div><p style="margin-top:12px">Mencari tikor terdekat...</p></div>';
    document.getElementById('result-count').textContent = 'Mencari...';
    document.getElementById('btn-find').disabled = true;

    // Center marker
    centerMarker = L.marker([lat, lng], { icon: centerIcon }).addTo(map);
    centerMarker.bindPopup('<b>📍 Titik Pencarian</b><br>' + lat.toFixed(6) + ', ' + lng.toFixed(6)).openPopup();
    
    // Radius circle
    radiusCircle = L.circle([lat, lng], {
        radius: parseInt(radius),
        color: '#dc2626',
        fillColor: '#dc2626',
        fillOpacity: 0.08,
        weight: 2,
        dashArray: '5, 5'
    }).addTo(map);

    map.setView([lat, lng], 18);

    // API call
    fetch('api/search_nearby.php?lat=' + lat + '&lng=' + lng + '&radius=' + radius)
        .then(r => r.json())
        .then(data => {
            document.getElementById('btn-find').disabled = false;
            if (data.error) {
                showToast('❌ Error: ' + data.error);
                document.getElementById('table-content').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>' + data.error + '</p></div>';
                return;
            }

            var results = data.results || [];
            document.getElementById('result-count').textContent = results.length + ' titik ditemukan';

            if (results.length === 0) {
                document.getElementById('table-content').innerHTML = '<div class="empty-state"><div class="icon">🔍</div><p>Tidak ada tikor dalam radius ' + radius + ' meter dari koordinat tersebut</p></div>';
                showToast('ℹ️ Tidak ada titik ditemukan dalam radius ' + radius + 'm');
                return;
            }

            // Add markers
            results.forEach(function(r) {
                if (r.lat && r.lng) {
                    var m = L.marker([r.lat, r.lng], { icon: tikorIcon }).addTo(map);
                    m.bindPopup(
                        '<b>' + (r.homepass_id || '-') + '</b><br>' +
                        (r.nama_jalan || '') + ' No.' + (r.no_rumah || '-') + '<br>' +
                        (r.kelurahan || '') + ', ' + (r.kecamatan || '') + '<br>' +
                        '<small>Jarak: ' + parseFloat(r.distance_m).toFixed(1) + ' m</small>'
                    );
                    markers.push(m);
                }
            });

            // Render table
            var html = '<table class="tbl"><thead><tr>' +
                '<th>No</th><th>HomepassID</th><th>Kode Pos</th><th>No Rumah</th>' +
                '<th>Nama Jalan</th><th>Kelurahan</th><th>Kecamatan</th><th>Kota</th>' +
                '<th>Tipe Rumah</th><th>Koordinat</th><th>Nama Klaster</th>' +
                '<th>Status</th><th>Jarak (m)</th><th>Action</th>' +
                '</tr></thead><tbody>';

            results.forEach(function(r, i) {
                var statusClass = (r.homepass_status || '').toLowerCase() === 'idle' ? 'badge-idle' :
                                  (r.homepass_status || '').toLowerCase() === 'active' ? 'badge-active' : 'badge-default';
                html += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td title="' + (r.homepass_id || '') + '">' + (r.homepass_id || '-') + '</td>' +
                    '<td>' + (r.kode_pos || '-') + '</td>' +
                    '<td>' + (r.no_rumah || '-') + '</td>' +
                    '<td title="' + (r.nama_jalan || '') + '">' + (r.nama_jalan || '-') + '</td>' +
                    '<td>' + (r.kelurahan || '-') + '</td>' +
                    '<td>' + (r.kecamatan || '-') + '</td>' +
                    '<td>' + (r.kota || '-') + '</td>' +
                    '<td>' + (r.resident_type || '-') + '</td>' +
                    '<td><small>' + (r.homepassed_koordinat || '-') + '</small></td>' +
                    '<td>' + (r.cluster_name || '-') + '</td>' +
                    '<td><span class="badge ' + statusClass + '">' + (r.homepass_status || '-') + '</span></td>' +
                    '<td><strong>' + parseFloat(r.distance_m).toFixed(1) + '</strong></td>' +
                    '<td><button class="btn-copy" onclick="copyCoord(\'' + (r.homepassed_koordinat || '') + '\')">📋 Copy</button></td>' +
                    '</tr>';
            });

            html += '</tbody></table>';
            document.getElementById('table-content').innerHTML = html;
            showToast('✅ Ditemukan ' + results.length + ' titik dalam radius ' + radius + 'm');
        })
        .catch(err => {
            document.getElementById('btn-find').disabled = false;
            showToast('❌ Gagal menghubungi server');
            document.getElementById('table-content').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>Gagal menghubungi server API</p></div>';
        });
}

function copyCoord(coord) {
    navigator.clipboard.writeText(coord).then(() => {
        showToast('✅ Koordinat disalin: ' + coord);
    }).catch(() => {
        var ta = document.createElement('textarea');
        ta.value = coord;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('✅ Koordinat disalin: ' + coord);
    });
}

function resetMap() {
    clearMarkers();
    document.getElementById('coord-input').value = '';
    document.getElementById('table-content').innerHTML = '<div class="empty-state"><div class="icon">🔍</div><p>Masukkan koordinat dan klik <strong>Find Nearby Points</strong> untuk mencari tikor terdekat</p></div>';
    document.getElementById('result-count').textContent = 'Belum ada pencarian';
    map.setView([-6.2088, 106.8456], 10);
}

var toastTimeout;
function showToast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => t.classList.remove('show'), 3000);
}

// Enter key trigger search
document.getElementById('coord-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') searchNearby();
});

// ===== HEARTBEAT - Keep session alive & detect force logout =====
function sendHeartbeat() {
    fetch('api/heartbeat.php')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'force_logout') {
                showToast('⚠️ Sesi Anda dihentikan oleh Admin!');
                setTimeout(() => { window.location.href = data.redirect; }, 2000);
            } else if (data.status === 'expired') {
                window.location.href = data.redirect;
            }
        })
        .catch(() => {}); // Silent fail on network error
}

// Send heartbeat every 2 minutes
// Delay first call 5 seconds to ensure session is saved to DB first
setTimeout(sendHeartbeat, 5000);
setInterval(sendHeartbeat, 120000);
</script>
<script src="assets/inactivity.js"></script>
</body>
</html>
