<?php
/**
 * Faculty Management System (FMS)
 * Vercel Serverless Function Gateway & Dispatcher
 * Developed by: Saniya Momin (Roll No: 124) & Tasmiya Shaikh (Roll No: 123)
 * Project Mentor: Assistant Professor Mahwish Momin
 */

// Parse requested URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$trimmed = trim($path, '/');

// 1. Static Asset Dispatcher (Never return HTML for CSS/JS/Image requests)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|map)$/i', $trimmed)) {
    // Collect potential physical disk paths
    $cleanAssetPath = preg_replace('#^(fms/assets/|assets/)#', '', $trimmed);
    $candidates = [
        __DIR__ . '/../' . $trimmed,
        __DIR__ . '/../fms/' . $trimmed,
        __DIR__ . '/../fms/assets/' . $cleanAssetPath,
        __DIR__ . '/../public/' . $trimmed,
        __DIR__ . '/../public/fms/' . $trimmed,
        __DIR__ . '/../public/assets/' . $cleanAssetPath,
        __DIR__ . '/../dist/' . $trimmed,
        __DIR__ . '/../dist/fms/' . $trimmed,
        __DIR__ . '/../dist/assets/' . $cleanAssetPath,
    ];

    foreach ($candidates as $cand) {
        if (file_exists($cand) && !is_dir($cand)) {
            $ext = strtolower(pathinfo($cand, PATHINFO_EXTENSION));
            $mimes = [
                'css'   => 'text/css; charset=UTF-8',
                'js'    => 'application/javascript; charset=UTF-8',
                'svg'   => 'image/svg+xml',
                'png'   => 'image/png',
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'gif'   => 'image/gif',
                'ico'   => 'image/x-icon',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf'   => 'font/ttf',
                'eot'   => 'application/vnd.ms-fontobject',
                'map'   => 'application/json',
            ];
            header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
            header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
            header('Content-Length: ' . filesize($cand));
            readfile($cand);
            exit;
        }
    }

    // Asset requested but not found: return clean 404 (NEVER return HTML, which causes browser MIME errors)
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "404 Asset Not Found: " . htmlspecialchars($trimmed);
    exit;
}

// 2. Resolve PHP Target Script
// Normalize requested route
if (empty($trimmed) || $trimmed === 'fms' || $trimmed === 'index.php') {
    $scriptRelative = 'fms/index.php';
} elseif (strpos($trimmed, 'fms/') === 0) {
    $scriptRelative = $trimmed;
} else {
    // Direct requests like /admin/dashboard.php or /login.php -> map into fms/
    $scriptRelative = 'fms/' . $trimmed;
}

$target = __DIR__ . '/../' . $scriptRelative;

// If target is directory, look for index.php inside it
if (is_dir($target)) {
    $target = rtrim($target, '/') . '/index.php';
    $scriptRelative = rtrim($scriptRelative, '/') . '/index.php';
} elseif (!file_exists($target) && file_exists($target . '.php')) {
    $target .= '.php';
    $scriptRelative .= '.php';
}

// Fallback to fms/index.php if target doesn't exist
if (!file_exists($target) || is_dir($target)) {
    $target = __DIR__ . '/../fms/index.php';
    $scriptRelative = 'fms/index.php';
}

// Update SCRIPT_NAME and PHP_SELF to match the target PHP file
// This ensures BASE_URL calculation and active menu checking in sidebars work accurately
$_SERVER['SCRIPT_NAME'] = '/' . ltrim($scriptRelative, '/');
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

// Execute target PHP application script
require $target;

