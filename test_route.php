<?php
// 测试路由配置
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? '',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? '',
    'GET' => $_GET,
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? '',
    'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? '',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
