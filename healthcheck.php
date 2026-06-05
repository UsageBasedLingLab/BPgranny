<?php
require_once 'config.php';

header('Content-Type: application/json');

$result = [
    'status' => 'ok',
    'database' => 'unknown',
    'timestamp' => date('c')
];

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($conn->connect_error) {
        $result['status'] = 'degraded';
        $result['database'] = 'failed: ' . $conn->connect_error;
        http_response_code(503);
    } else {
        $result['database'] = 'connected';
        $conn->close();
    }
} catch (Exception $e) {
    $result['status'] = 'degraded';
    $result['database'] = 'error: ' . $e->getMessage();
    http_response_code(503);
}

echo json_encode($result, JSON_PRETTY_PRINT);
