<?php

require_once '../vendor/autoload.php'; // Stub - manual no vendor yet

$url = $_SERVER['REQUEST_URI'];
if (strpos($url, '/api/') === 0) {
    chdir(__DIR__ . '/..');
    require 'routes/api.php';
} else {
    http_response_code(404);
    echo 'API Backend Ready. Run php -S localhost:8000 -t backend';
}

