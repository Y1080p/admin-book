<?php
// 1. 优先处理OPTIONS预请求（AJAX跨域必加，避免浏览器拦截）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置信任的跨域域名（本地开发+线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 替换为你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回CORS头（安全+兼容Session）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true'); // 关键：传递Session必须加
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 适配AJAX常用请求
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置避免格式混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有Session逻辑（必须移到CORS之后，避免header报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充401状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401未授权，前端可通过状态码快速判断
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 设置响应头为JSON
header('Content-Type: application/json');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
    exit();
}

// 获取并验证表单数据
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$color = isset($_POST['color']) ? trim($_POST['color']) : '#409eff';
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// 验证必填字段
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => '标签名称不能为空']);
    exit();
}

if (empty($color)) {
    echo json_encode(['success' => false, 'message' => '标签颜色不能为空']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查标签是否已存在
    $checkSql = "SELECT id FROM tags WHERE name = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$name]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => '该标签已存在']);
        exit();
    }
    
    // 插入新标签数据
    $insertSql = "INSERT INTO tags (name, color, status, create_time) 
                  VALUES (?, ?, ?, NOW())";
    
    $insertStmt = $pdo->prepare($insertSql);
    $result = $insertStmt->execute([
        $name, 
        $color, 
        $status
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '标签添加成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '添加失败，请重试']);
    }
    
} catch (PDOException $e) {
    // 记录错误日志
    error_log('添加标签失败: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '数据库操作失败']);
}
?>
