/**
 * Cookie Consent & Performance Cache Booster for Support Map Tikor
 * Optimizes local storage caching, map tiles caching, and session persistence.
 */
(function () {
    var COOKIE_NAME = 'tikor_cookie_consent';
    var COOKIE_EXP_DAYS = 365;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : (localStorage.getItem(name) || null);
    }

    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
        try {
            localStorage.setItem(name, value);
        } catch (e) {}
    }

    function enablePerformanceOptimizations() {
        // Cache optimization for smoother rendering
        try {
            // DNS prefetch / preconnect for map tile servers
            var domains = [
                'https://tile.openstreetmap.org',
                'https://server.arcgisonline.com',
                'https://fonts.googleapis.com',
                'https://fonts.gstatic.com',
                'https://unpkg.com'
            ];
            domains.forEach(function (href) {
                if (!document.querySelector('link[href="' + href + '"]')) {
                    var link = document.createElement('link');
                    link.rel = 'preconnect';
                    link.href = href;
                    link.crossOrigin = '';
                    document.head.appendChild(link);
                }
            });
        } catch (e) {}
    }

    function initCookieBanner() {
        var consent = getCookie(COOKIE_NAME);
        if (consent) {
            enablePerformanceOptimizations();
            return;
        }

        var banner = document.createElement('div');
        banner.id = 'tikor-cookie-banner';
        banner.innerHTML = [
            '<div style="position:fixed;bottom:24px;left:24px;right:24px;max-width:480px;margin:0 0 0 auto;z-index:99999;',
            'background:rgba(11,21,45,0.96);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);',
            'border:1px solid rgba(56,189,248,0.3);border-radius:16px;padding:20px 22px;',
            'box-shadow:0 12px 40px rgba(0,0,0,0.6), 0 0 20px rgba(14,165,233,0.2);',
            'font-family:\'Inter\',sans-serif;color:#f1f5f9;animation:cookieSlideIn .35s cubic-bezier(0.16,1,0.3,1)">',
            
            '<div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px">',
            '<div style="font-size:28px;line-height:1;filter:drop-shadow(0 2px 8px rgba(245,158,11,0.4))">🍪</div>',
            '<div style="flex:1">',
            '<h4 style="font-size:15px;font-weight:700;color:#f8fafc;margin:0 0 4px">Optimasi Performa & Cookie</h4>',
            '<p style="font-size:12.5px;color:#94a3b8;margin:0;line-height:1.55">',
            'Kami menggunakan cookie dan cache lokal untuk mempercepat loading peta tikor, mencegah lag saat navigasi, dan menjaga kenyamanan sesi Anda.',
            '</p>',
            '</div>',
            '</div>',
            
            '<div style="display:flex;gap:10px;justify-content:flex-end;align-items:center;flex-wrap:wrap">',
            '<button id="btn-cookie-essential" style="background:rgba(255,255,255,0.08);color:#cbd5e1;',
            'border:1px solid rgba(255,255,255,0.18);padding:8px 14px;border-radius:8px;font-size:12.5px;',
            'font-weight:500;cursor:pointer;font-family:inherit;transition:all .2s">Hanya Esensial</button>',
            
            '<button id="btn-cookie-accept" style="background:linear-gradient(135deg,#0284c7,#0ea5e9);color:white;',
            'border:none;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;',
            'font-family:inherit;box-shadow:0 4px 14px rgba(14,165,233,0.35);transition:all .2s">',
            '✅ Terima Semua Cookie',
            '</button>',
            '</div>',
            
            '</div>'
        ].join('');

        // Add keyframe animation style
        if (!document.getElementById('cookie-anim-style')) {
            var style = document.createElement('style');
            style.id = 'cookie-anim-style';
            style.textContent = '@keyframes cookieSlideIn { from { transform: translateY(40px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }';
            document.head.appendChild(style);
        }

        document.body.appendChild(banner);

        document.getElementById('btn-cookie-accept').addEventListener('click', function () {
            setCookie(COOKIE_NAME, 'accepted', COOKIE_EXP_DAYS);
            enablePerformanceOptimizations();
            banner.remove();
        });

        document.getElementById('btn-cookie-essential').addEventListener('click', function () {
            setCookie(COOKIE_NAME, 'essential', COOKIE_EXP_DAYS);
            enablePerformanceOptimizations();
            banner.remove();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCookieBanner);
    } else {
        initCookieBanner();
    }
})();
