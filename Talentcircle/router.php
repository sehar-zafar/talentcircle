<?php

// Simple router to serve both:
// - Frontend static HTML files from project root (e.g., /signin.html)
// - API endpoints from backend/routes/api.php for any /api/* request
//
// Run:
//   cd c:/xampp/htdocs/talentcircle/Talentcircle
//   php -S localhost:8000 router.php
//
// Then open:
//   http://localhost:8000/signin.html
//   http://localhost:8000/api/login

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($path, '/api/') === 0 || $path === '/api' || $path === '/api/') {
    require __DIR__ . '/backend/routes/api.php';
    exit;
}

// Serve static frontend files from the project root.
// If no extension is provided, try adding .html.
$root = __DIR__;

// Default route
if ($path === '/' || $path === '') {
    $candidate = $root . '/home.html';
    if (file_exists($candidate)) {
        readfile($candidate);
        exit;
    }
    http_response_code(404);
    echo 'Not found';
    exit;
}

$fsPath = $root . $path;

// If request is for a directory, try index.html
if (is_dir($fsPath)) {
    $candidate = $fsPath . '/index.html';
    if (file_exists($candidate)) {
        readfile($candidate);
        exit;
    }
    http_response_code(404);
    echo 'Not found';
    exit;
}

// If file doesn't exist, try .html (e.g., /signin -> /signin.html)
if (!file_exists($fsPath)) {
    $candidate = $fsPath . '.html';
    if (file_exists($candidate)) {
        readfile($candidate);
        exit;
    }
    http_response_code(404);
    echo 'Not found';
    exit;
}

// Serve the file
$ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));

// Basic content-type handling
if ($ext === 'html') header('Content-Type: text/html; charset=utf-8');
elseif ($ext === 'js') header('Content-Type: application/javascript; charset=utf-8');
elseif ($ext === 'css') header('Content-Type: text/css; charset=utf-8');
elseif ($ext === 'png') header('Content-Type: image/png');
elseif ($ext === 'jpg' || $ext === 'jpeg') header('Content-Type: image/jpeg');
elseif ($ext === 'svg') header('Content-Type: image/svg+xml');
elseif ($ext === 'json') header('Content-Type: application/json; charset=utf-8');
else header('Content-Type: application/octet-stream');

readfile($fsPath);
exit;

