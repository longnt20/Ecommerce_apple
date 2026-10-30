<?php

// Used only by portfolio-demo.php's loopback PHP server, never registered as an application route.
$cache = getenv('APP_CONFIG_CACHE') ?: '';
if (PHP_SAPI !== 'cli-server' || ! preg_match('~^tests/\.runtime/p7-[a-f0-9]{12}-config\.php$~D', $cache)) {
    http_response_code(403);
    exit;
}
$root = dirname(__DIR__, 2);
$config = require $root.'/'.$cache;
$demo = $config['portfolio_demo'] ?? [];
$db = $config['database']['connections']['mysql']['database'] ?? '';
if (! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', $db) || ! preg_match('/^[a-f0-9]{12}$/D', $demo['id'] ?? '')
    || ($_SERVER['HTTP_HOST'] ?? '') !== '127.0.0.1:'.($demo['port'] ?? '')) {
    http_response_code(403);
    exit;
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$reply = static function (array $body, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
};
if ($path === '/_demo/health') {
    $reply(['id' => $demo['id'], 'synthetic' => true]);
}
if ($path === '/api/provinces') {
    $reply([['code' => 1, 'name' => 'Demo Province']]);
}
if (preg_match('~^/api/districts/\d+$~D', $path)) {
    $reply(['districts' => [['code' => 1, 'name' => 'Demo District']]]);
}
if (preg_match('~^/api/wards/\d+$~D', $path)) {
    $reply(['wards' => [['code' => 1, 'name' => 'Demo Ward']]]);
}
if (preg_match('~^/api/(auth/google|vnpay|chat|ai)(/|$)~', $path)) {
    $reply(['message' => 'External provider disabled in synthetic portfolio demo.'], 503);
}
// Do not expose source uploads, Telescope, dotfiles, arbitrary scripts or traversal.
if (str_contains($path, '..') || preg_match('~(^|/)(\.|storage|telescope)~', $path) || str_ends_with($path, '.php')) {
    http_response_code(404);
    exit;
}
$static = realpath($root.'/public'.$path);
if ($static && str_starts_with($static, realpath($root.'/public').DIRECTORY_SEPARATOR) && is_file($static)) {
    return false;
}
require $root.'/public/index.php';
