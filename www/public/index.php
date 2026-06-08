<?php
header('Content-Type: application/json');

$response = [
    'status' => 'ok',
    'app' => 'EIDOC',
    'php_version' => phpversion(),
    'timestamp' => date('c'),
];

// Test PostgreSQL connection
try {
    $dsn = 'pgsql:host=db;port=5432;dbname=eidoc';
    $pdo = new PDO($dsn, 'eidoc', 'eidoc_pass', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $stmt = $pdo->query('SELECT version()');
    $response['database'] = [
        'connected' => true,
        'version' => $stmt->fetchColumn(),
    ];
} catch (Exception $e) {
    $response['database'] = [
        'connected' => false,
        'error' => $e->getMessage(),
    ];
}

// Test Redis connection
try {
    $redis = fsockopen('redis', 6379, $errno, $errstr, 2);
    if ($redis) {
        fwrite($redis, "PING\r\n");
        $redisResponse = trim(fgets($redis));
        fclose($redis);
        $response['redis'] = [
            'connected' => true,
            'ping' => $redisResponse,
        ];
    } else {
        $response['redis'] = ['connected' => false, 'error' => $errstr];
    }
} catch (Exception $e) {
    $response['redis'] = ['connected' => false, 'error' => $e->getMessage()];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
