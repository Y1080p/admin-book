<?php
// 1. 优先处理OPTIONS预请求（跨域AJAX必加，避免浏览器拦截）
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

// 3. 仅给信任域名返回CORS头（安全+支持Session传递）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true'); // 关键：无此配置Session会失效
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 适配AJAX常用请求
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置配置，避免格式混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 启动Session（必须在CORS之后，避免header报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充401状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401=未授权，前端可快速识别
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 引入数据库连接（原有逻辑完全保留）
require_once '../SQL Connection/db_connect.php';

// 获取POST参数
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// 验证参数
if ($category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

if (!in_array($status, [0, 1])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '状态值无效']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查分类是否存在
    $checkSql = "SELECT id FROM categories WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$category_id]);
    
    if (!$checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类不存在']);
        exit();
    }
    
    // 更新分类状态
    $updateSql = "UPDATE categories SET status = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([$status, $category_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'newStatus' => $status]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '更新失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>
