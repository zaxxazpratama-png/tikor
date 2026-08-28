<?php
/**
 * Vercel Entry Point Router
 * Semua request dari Vercel masuk ke sini, lalu di-route ke file PHP yang sesuai.
 */

// Buffer output agar session_start() tidak konflik dengan HTML output
ob_start();

// Root project adalah satu level di atas folder api/
define('APP_ROOT', dirname(__DIR__));
define('IS_VERCEL', (bool) getenv('VERCEL'));

// Inisialisasi session SEBELUM apapun di-include (sebelum ada output HTML)
require_once APP_ROOT . '/config/session.php';
initMysqlSession();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Ambil path yang diminta (tanpa query string)
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$path        = parse_url($requestUri, PHP_URL_PATH);
$path        = rtrim($path, '/') ?: '/';

// Map path ke file PHP di root project
$routeMap = [
    '/'                  => '/index.php',
    '/index.php'         => '/index.php',
    '/login'             => '/login.php',
    '/login.php'         => '/login.php',
    '/logout'            => '/logout.php',
    '/logout.php'        => '/logout.php',
    '/dashboard'         => '/dashboard.php',
    '/dashboard.php'     => '/dashboard.php',
    '/view_data'         => '/view_data.php',
    '/view_data.php'     => '/view_data.php',
    '/import_data'       => '/import_data.php',
    '/import_data.php'   => '/import_data.php',
    '/create_user'       => '/create_user.php',
    '/create_user.php'   => '/create_user.php',
    '/manage_devices'    => '/manage_devices.php',
    '/manage_devices.php'=> '/manage_devices.php',
    '/change_password'   => '/change_password.php',
    '/change_password.php'=> '/change_password.php',
    '/reset_password'    => '/reset_password.php',
    '/reset_password.php'=> '/reset_password.php',
    '/download_log'      => '/download_log.php',
    '/download_log.php'  => '/download_log.php',
    '/403'               => '/403.php',
    '/403.php'           => '/403.php',
    // API endpoints
    '/api/search_nearby' => '/api/search_nearby.php',
    '/api/search_nearby.php' => '/api/search_nearby.php',
    '/api/heartbeat'     => '/api/heartbeat.php',
    '/api/heartbeat.php' => '/api/heartbeat.php',
];

// Resolve file target
$targetFile = $routeMap[$path] ?? null;

// Jika tidak ada di routeMap, coba langsung sebagai file PHP
if ($targetFile === null) {
    $candidate = APP_ROOT . $path;
    if (file_exists($candidate) && !is_dir($candidate)) {
        // File statis (CSS, JS, images, dll) — serve langsung
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
            readfile($candidate);
            exit;
        }
        if ($ext === 'php') {
            chdir(APP_ROOT);
            include $candidate;
            exit;
        }
    }
    // 404
    http_response_code(404);
    echo '<h1>404 - Halaman tidak ditemukan</h1>';
    exit;
}

// Jalankan file PHP yang ditarget
$fullPath = APP_ROOT . $targetFile;
if (!file_exists($fullPath)) {
    http_response_code(404);
    echo '<h1>404 - File tidak ditemukan: ' . htmlspecialchars($targetFile) . '</h1>';
    exit;
}

// Pindah ke root project agar require_once dan path relatif bekerja
chdir(APP_ROOT);
include $fullPath;
