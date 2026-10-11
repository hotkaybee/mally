<?php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$publicFile = __DIR__.'/public'.$uri;

if ($uri !== '/' && file_exists($publicFile) && ! is_dir($publicFile)) {
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'otf' => 'font/otf',
        'txt' => 'text/plain',
    ];

    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    $contentType = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($publicFile) : 'application/octet-stream');
    header('Content-Type: '.$contentType);
    header('Content-Length: '.filesize($publicFile));
    readfile($publicFile);
    exit;
}

require_once __DIR__.'/public/index.php';
