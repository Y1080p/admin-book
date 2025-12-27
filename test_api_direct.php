<?php
// 简单测试API和数据库连接
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$result = [
    'test' => 'API直接访问测试',
    'timestamp' => date('Y-m-d H:i:s'),
];

// 测试数据库连接
try {
    require_once 'SQL Connection/db_connect.php';
    $pdo = getPDOConnection();
    $result['database'] = 'success';
    $result['database_connection'] = 'connected';

    // 测试简单查询
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $row = $stmt->fetch();
    $result['user_count'] = $row['count'];
} catch (Exception $e) {
    $result['database'] = 'failed';
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
