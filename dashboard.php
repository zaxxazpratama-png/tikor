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
    <link rel="icon" type="image/png" href="assets/logo-tin.png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #070e22 0%, #030712 100%);
            min-height: 100vh;
            color: #f1f5f9;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #040817 0%, #0b1836 100%);
            color: white;
            padding: 0 20px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-brand {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f8fafc;
        }
        .header-logo {
            height: 32px;
            width: auto;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.95);
            padding: 3px 8px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .header-user {
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }
        .btn-header {
            background: rgba(255,255,255,0.08);
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.18);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-header:hover {
            background: rgba(14, 165, 233, 0.2);
            border-color: rgba(56, 189, 248, 0.4);
            color: white;
        }

        /* Controls */
        .controls-bar {
            background: rgba(11, 21, 45, 0.95);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid rgba(56, 189, 248, 0.15);
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }
        .controls-bar label {
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            white-space: nowrap;
        }
        .coord-input {
            padding: 9px 14px;
            background: #0f1c3a;
            border: 1.5px solid #1c325c;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            width: 260px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .coord-input::placeholder { color: #64748b; }
        .coord-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
            background: #132347;
        }
        .radius-select {
            padding: 9px 12px;
            background: #0f1c3a;
            border: 1.5px solid #1c325c;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .radius-select:focus {
            border-color: #0ea5e9;
            background: #132347;
        }
        .btn-search {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
        }
        .btn-search:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5);
        }
        .btn-reset {
            background: rgba(255,255,255,0.06);
            color: #cbd5e1;
            border: 1px solid rgba(255,255,255,0.15);
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background 0.2s, color 0.2s;
        }
        .btn-reset:hover {
            background: rgba(255,255,255,0.12);
            color: white;
        }

        /* Map */
        #map {
            height: 440px;
            width: 100%;
            z-index: 1;
            border-bottom: 1px solid rgba(56, 189, 248, 0.15);
        }

        /* Table area */
        .table-section {
            padding: 24px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .table-title {
            font-size: 17px;
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .result-count {
            font-size: 13px;
            color: #38bdf8;
            background: rgba(14, 165, 233, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: 600;
        }
        .table-wrapper {
            background: rgba(11, 21, 45, 0.95);
            border-radius: 14px;
            border: 1px solid rgba(56, 189, 248, 0.2);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 8px 32px rgba(0,0,0,0.45);
        }
        .tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 850px;
        }
        .tbl thead tr {
            background: linear-gradient(135deg, #0c1d3f, #132b5b);
            color: #38bdf8;
            border-bottom: 1px solid rgba(56, 189, 248, 0.25);
        }
        .tbl thead th {
            padding: 13px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .tbl tbody tr {
            border-bottom: 1px solid #132347;
            transition: background 0.15s;
        }
        .tbl tbody tr:hover { background: rgba(14, 165, 233, 0.08); }
        .tbl tbody td {
            padding: 11px 12px;
            color: #cbd5e1;
            max-width: 190px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-idle {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .badge-active {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-default {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        .btn-copy {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: white;
            border: none;
            padding: 5px 12px;
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
            color: #64748b;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 15px; color: #94a3b8; }
        .empty-state p strong { color: #38bdf8; }
        .loading-state {
            text-align: center;
            padding: 45px 20px;
            color: #94a3b8;
        }
        .spinner {
            display: inline-block;
            width: 34px;
            height: 34px;
            border: 3px solid #1e293b;
            border-top-color: #0ea5e9;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0b152d;
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #f8fafc;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .toast.show { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .header-brand { font-size: 16px; }
            .header-user { display: none; }
            .btn-header { padding: 5px 10px; font-size: 12px; }
            .controls-bar { padding: 12px; gap: 8px; }
            .coord-input { width: 100%; }
            .radius-select { width: 100%; }
            .btn-search { width: 100%; }
            .btn-reset { width: 100%; }
            .table-section { padding: 14px; }
            .table-title::after { content: " (geser ↔)"; font-size: 11px; font-weight: normal; color: #38bdf8; }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-brand">
        <img src="assets/logo-tin.png" alt="PT. TIN" class="header-logo">
        <span>SUPPORT MAP</span>
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

// Custom Blue Pin for search center
var bluePinIcon = L.divIcon({
    className: 'custom-blue-pin',
    html: '<div style="position:relative; display:flex; flex-direction:column; align-items:center;">' +
          '<div style="background:#1d4ed8; color:white; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px; margin-bottom:2px; white-space:nowrap; box-shadow:0 2px 5px rgba(0,0,0,0.3); border:1px solid #93c5fa;">Titik Pencarian</div>' +
          '<svg width="28" height="38" viewBox="0 0 28 38" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 3px 6px rgba(0,0,0,0.35));">' +
          '<path d="M14 0C6.268 0 0 6.268 0 14C0 24.5 14 38 14 38C14 38 28 24.5 28 14C28 6.268 21.732 0 14 0Z" fill="#1D4ED8"/>' +
          '<path d="M14 1.5C7.096 1.5 1.5 7.096 1.5 14C1.5 23.3 14 35.5 14 35.5C14 35.5 26.5 23.3 26.5 14C26.5 7.096 20.904 1.5 14 1.5Z" fill="#2563EB"/>' +
          '<circle cx="14" cy="13" r="5" fill="white"/>' +
          '</svg></div>',
    iconSize: [90, 58],
    iconAnchor: [45, 58],
    popupAnchor: [0, -58]
});

// Click on map to get coordinates & automatically search in real-time
map.on('click', function(e) {
    var lat = e.latlng.lat.toFixed(8);
    var lng = e.latlng.lng.toFixed(8);
    document.getElementById('coord-input').value = lat + ', ' + lng;
    searchNearby();
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
        showToast('⚠️ Klik pada peta atau masukkan koordinat terlebih dahulu!');
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

    // Center marker (Blue Pin)
    centerMarker = L.marker([lat, lng], { 
        icon: bluePinIcon,
        draggable: true 
    }).addTo(map);

    centerMarker.bindPopup('<b>📍 Titik Pencarian</b><br>' + lat.toFixed(6) + ', ' + lng.toFixed(6)).openPopup();
    
    // Draggable blue pin updates coordinates & triggers search in real time
    centerMarker.on('dragend', function(ev) {
        var pos = ev.target.getLatLng();
        var newLat = pos.lat.toFixed(8);
        var newLng = pos.lng.toFixed(8);
        document.getElementById('coord-input').value = newLat + ', ' + newLng;
        searchNearby();
    });

    // Radius circle (Blue style)
    radiusCircle = L.circle([lat, lng], {
        radius: parseInt(radius),
        color: '#2563eb',
        fillColor: '#3b82f6',
        fillOpacity: 0.12,
        weight: 2,
        dashArray: '5, 5'
    }).addTo(map);

    if (map.getZoom() < 17) {
        map.setView([lat, lng], 18);
    } else {
        map.panTo([lat, lng]);
    }

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
                '<th>No</th><th>HomepassID</th><th>Kode Pos</th><th>Nama Jalan</th><th>No Rumah</th>' +
                '<th>Kelurahan</th><th>Kecamatan</th><th>Kota</th>' +
                '<th>Tipe Rumah</th><th>Koordinat</th><th>Nama Klaster</th>' +
                '<th>Status</th><th>Jarak (m)</th><th>Action</th>' +
                '</tr></thead><tbody>';

            results.forEach(function(r, i) {
                var statusClass = (r.homepass_status || '').toLowerCase() === 'idle' ? 'badge-idle' :
                                  (r.homepass_status || '').toLowerCase() === 'active' ? 'badge-active' : 'badge-default';
                
                var coordVal = r.homepassed_koordinat || ((r.lat && r.lng) ? (r.lat + ', ' + r.lng) : '-');
                
                var rowData = {
                    homepass_id: r.homepass_id || '',
                    kode_pos: r.kode_pos || '',
                    no_rumah: r.no_rumah || '',
                    nama_jalan: r.nama_jalan || '',
                    kelurahan: r.kelurahan || '',
                    kecamatan: r.kecamatan || '',
                    kota: r.kota || '',
                    resident_type: r.resident_type || '',
                    koordinat: coordVal !== '-' ? coordVal : ''
                };
                var encodedData = encodeURIComponent(JSON.stringify(rowData));

                html += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td title="' + (r.homepass_id || '') + '">' + (r.homepass_id || '-') + '</td>' +
                    '<td>' + (r.kode_pos || '-') + '</td>' +
                    '<td title="' + (r.nama_jalan || '') + '">' + (r.nama_jalan || '-') + '</td>' +
                    '<td>' + (r.no_rumah || '-') + '</td>' +
                    '<td>' + (r.kelurahan || '-') + '</td>' +
                    '<td>' + (r.kecamatan || '-') + '</td>' +
                    '<td>' + (r.kota || '-') + '</td>' +
                    '<td>' + (r.resident_type || '-') + '</td>' +
                    '<td><small>' + coordVal + '</small></td>' +
                    '<td>' + (r.cluster_name || '-') + '</td>' +
                    '<td><span class="badge ' + statusClass + '">' + (r.homepass_status || '-') + '</span></td>' +
                    '<td><strong>' + parseFloat(r.distance_m).toFixed(1) + '</strong></td>' +
                    '<td><button class="btn-copy" onclick="copyTikorData(\'' + encodedData + '\')">📋 Copy</button></td>' +
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

function copyTikorData(encodedJson) {
    try {
        var d = JSON.parse(decodeURIComponent(encodedJson));
        var textToCopy = [
            d.homepass_id,
            d.kode_pos,
            d.no_rumah,
            d.nama_jalan,
            d.kelurahan,
            d.kecamatan,
            d.kota,
            d.resident_type,
            d.koordinat
        ].join('\t');

        var onDone = function() {
            showToast('✅ Data disalin: ' + (d.homepass_id || '-') + ' (' + (d.nama_jalan || '-') + ')');
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(onDone).catch(function() {
                fallbackCopy(textToCopy, onDone);
            });
        } else {
            fallbackCopy(textToCopy, onDone);
        }
    } catch (e) {
        showToast('❌ Gagal memproses data copy');
    }
}

function fallbackCopy(text, callback) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.top = '0';
    ta.style.left = '0';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try {
        document.execCommand('copy');
        if (callback) callback();
    } catch (err) {
        showToast('❌ Gagal menyalin');
    }
    document.body.removeChild(ta);
}

function resetMap() {
    clearMarkers();
    document.getElementById('coord-input').value = '';
    document.getElementById('table-content').innerHTML = '<div class="empty-state"><div class="icon">🔍</div><p>Klik di peta atau masukkan koordinat untuk mencari tikor terdekat secara realtime</p></div>';
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

// Auto search on radius change if coordinates already set
document.getElementById('radius-select').addEventListener('change', function() {
    var coordVal = document.getElementById('coord-input').value.trim();
    if (coordVal) {
        searchNearby();
    }
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
<script src="assets/cookie_consent.js"></script>
</body>
</html>
