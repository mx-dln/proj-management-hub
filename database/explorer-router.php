<?php
// Router for the PHP development server; Apache uses the root .htaccess instead.
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
ini_set('session.save_path', dirname(__DIR__) . '/logs/sessions');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('~^/(?:config|models|controllers|includes|layouts|database|logs|vendor|backup[^/]*|\.git|uploads/explorer|uploads/documents/private)(?:/|$)|^/composer\.(?:json|lock)$~i', $path) || str_contains($path, '..')) {
    http_response_code(403); exit('Forbidden');
}
return false;
