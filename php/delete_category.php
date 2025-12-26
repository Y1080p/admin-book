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

// 3. 仅给信任域名返回CORS头（安全，避免恶意调用）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true'); // 必须加，否则Session无法传递
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 适配AJAX常用请求方法
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置配置，避免格式混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有Session逻辑（必须移到CORS之后，避免header报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充401状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 = 未授权，前端可通过状态码快速判断
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';
// 获取POST参数
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

// 验证参数
if ($category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
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
    
    // 检查是否有图书关联此分类
    $checkBooksSql = "SELECT id FROM books WHERE category_id = ? LIMIT 1";
    $checkBooksStmt = $pdo->prepare($checkBooksSql);
    $checkBooksStmt->execute([$category_id]);
    
    if ($checkBooksStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '该分类下有关联的图书，无法删除']);
        exit();
    }
    
    // 删除分类
    $deleteSql = "DELETE FROM categories WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([$category_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '分类删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>
