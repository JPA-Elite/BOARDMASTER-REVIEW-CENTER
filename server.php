<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$publicPath = __DIR__.'/public'.$uri;

if ($uri !== '/' && $uri !== '' && file_exists($publicPath)) {
    if (str_starts_with($uri, '/storage/')) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowedOrigins = array_filter(
            array_map(
                'trim',
                explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '')
            )
        );
        $allowOrigin = $allowedOrigins
            ? (in_array($origin, $allowedOrigins, true) ? $origin : $allowedOrigins[0])
            : '*';

        header('Access-Control-Allow-Origin: '.$allowOrigin);
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        header('Vary: Origin');
    }

    $mime = mime_content_type($publicPath) ?: 'application/octet-stream';
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($publicPath));
    readfile($publicPath);
    return true;
}

require_once __DIR__.'/public/index.php';
