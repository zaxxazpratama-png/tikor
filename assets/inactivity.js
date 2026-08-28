/**
 * Auto-logout after 5 minutes of inactivity
 * Shows a 60-second countdown warning before logging out
 */
(function () {
    var TIMEOUT_MS  = 5 * 60 * 1000;  // 5 minutes
    var WARNING_MS  = 60 * 1000;       // show warning 60s before logout
    var LOGOUT_URL  = 'logout.php';

    var inactiveTimer  = null;
    var warningTimer   = null;
    var countdownTimer = null;

    // ── Build warning modal ────────────────────────────────────────────
    var overlay = document.createElement('div');
    overlay.id  = 'inactivity-overlay';
    overlay.style.cssText = [
        'display:none', 'position:fixed', 'inset:0',
        'background:rgba(0,0,0,0.6)', 'z-index:9999',
        'align-items:center', 'justify-content:center',
        'font-family:Inter,sans-serif'
    ].join(';');

    overlay.innerHTML = [
        '<div style="background:white;border-radius:16px;padding:36px 32px;',
        'max-width:380px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3)">',
        '<div style="font-size:48px;margin-bottom:12px">⏱️</div>',
        '<h2 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:8px">',
        'Sesi Hampir Berakhir</h2>',
        '<p style="color:#6b7280;font-size:14px;margin-bottom:6px;line-height:1.6">',
        'Tidak ada aktivitas terdeteksi.</p>',
        '<p style="color:#374151;font-size:14px;margin-bottom:24px">',
        'Anda akan otomatis logout dalam ',
        '<span id="inact-count" style="font-size:22px;font-weight:800;color:#dc2626">60</span>',
        ' detik.</p>',
        '<button id="inact-stay" style="background:linear-gradient(135deg,#2d6a4f,#40916c);',
        'color:white;border:none;padding:12px 28px;border-radius:10px;font-size:15px;',
        'font-weight:600;cursor:pointer;font-family:inherit">',
        'Tetap Login</button>',
        '</div>'
    ].join('');

    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(overlay);
        document.getElementById('inact-stay').addEventListener('click', resetTimers);
        startTimers();
    });

    // ── Activity events ────────────────────────────────────────────────
    var events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
    events.forEach(function (ev) {
        document.addEventListener(ev, resetTimers, { passive: true });
    });

    // ── Timer logic ────────────────────────────────────────────────────
    function startTimers() {
        clearAllTimers();
        // Warning fires TIMEOUT_MS - WARNING_MS after last activity
        warningTimer = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS);
        // Hard logout fires TIMEOUT_MS after last activity
        inactiveTimer = setTimeout(doLogout, TIMEOUT_MS);
    }

    function resetTimers() {
        if (overlay.style.display === 'flex') {
            // User clicked "Tetap Login" while warning visible
            hideWarning();
        }
        startTimers();
    }

    function clearAllTimers() {
        clearTimeout(inactiveTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownTimer);
    }

    function showWarning() {
        var count = 60;
        document.getElementById('inact-count').textContent = count;
        overlay.style.display = 'flex';
        countdownTimer = setInterval(function () {
            count--;
            var el = document.getElementById('inact-count');
            if (el) el.textContent = count;
            if (count <= 0) {
                clearInterval(countdownTimer);
                doLogout();
            }
        }, 1000);
    }

    function hideWarning() {
        overlay.style.display = 'none';
        clearInterval(countdownTimer);
    }

    function doLogout() {
        clearAllTimers();
        overlay.style.display = 'none';
        window.location.href = LOGOUT_URL + '?reason=inactivity';
    }
})();
