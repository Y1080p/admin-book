<?php
// 测试 API 端点 - 用于诊断 Render 部署问题
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'API 测试成功！',
    'server_info' => [
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'https' => isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'off',
        'port' => $_SERVER['SERVER_PORT'] ?? 'unknown'
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
