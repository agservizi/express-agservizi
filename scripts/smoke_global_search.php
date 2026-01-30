<?php
declare(strict_types=1);

$root = dirname(__DIR__);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => 1,
    'username' => 'smoke',
    'role_id' => 1,
    'fullname' => 'Smoke Test',
];

$_GET['page'] = 'global_search';
$_GET['q'] = $argv[1] ?? 'test';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

require $root . '/public/index.php';
