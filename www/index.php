<?php
header('Content-Type: text/html; charset=utf-8');

$redisStatus = 'unavailable';
$redisSocket = @fsockopen('redis', 6379, $errno, $errstr, 2.0);
if ($redisSocket) {
  fwrite($redisSocket, "PING\r\n");
  $response = fgets($redisSocket);
  fclose($redisSocket);
  $redisStatus = str_contains((string) $response, 'PONG') ? 'ok' : 'error';
} else {
  $redisStatus = 'error: ' . $errstr;
}

$dbStatus = 'unavailable';
$dbInfo = [];
try {
    $pdo = new PDO(
        'pgsql:host=db;port=5432;dbname=eidoc',
        'eidoc',
        'eidoc_pass',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbInfo = $pdo->query('SELECT version() AS version, NOW() AS server_time')->fetch(PDO::FETCH_ASSOC);
    $dbStatus = 'ok';
} catch (Throwable $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

echo '<!DOCTYPE html><html><head><title>EIDOC Dev Environment</title></head><body>';
echo '<h1>EIDOC development environment</h1>';
echo '<p>PHP version: ' . htmlspecialchars(PHP_VERSION) . '</p>';
echo '<h2>Service checks</h2><ul>';
echo '<li>PostgreSQL: <strong>' . htmlspecialchars($dbStatus) . '</strong></li>';
echo '<li>Redis: <strong>' . htmlspecialchars($redisStatus) . '</strong></li>';
echo '</ul>';
if ($dbStatus === 'ok') {
    echo '<pre>' . htmlspecialchars(print_r($dbInfo, true)) . '</pre>';
}
echo '</body></html>';
