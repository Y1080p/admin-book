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
    'https://stunning-biscochitos-49d12b.netlify.app' // 替换成你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回CORS头（安全规范）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    // 适配AJAX接口的常用请求方法
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 提前设置JSON响应格式（和原有逻辑一致，统一前置）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有session逻辑（移到CORS之后，避免header报错）
session_start();

// 6. 原有登录检查逻辑（完全保留，JSON返回不变）
if (!isset($_SESSION['user_id'])) {
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
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$author = isset($_POST['author']) ? trim($_POST['author']) : '';
$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
$publisher = isset($_POST['publisher']) ? trim($_POST['publisher']) : '';
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// 验证必填字段
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => '图书标题不能为空']);
    exit();
}

if (empty($author)) {
    echo json_encode(['success' => false, 'message' => '作者不能为空']);
    exit();
}

if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => '请选择分类']);
    exit();
}

if ($price < 0) {
    echo json_encode(['success' => false, 'message' => '价格不能为负数']);
    exit();
}

if ($stock < 0) {
    echo json_encode(['success' => false, 'message' => '库存不能为负数']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查图书是否已存在（根据标题和作者）
    $checkSql = "SELECT id FROM books WHERE title = ? AND author = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$title, $author]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => '该图书已存在']);
        exit();
    }
    
    // 插入新图书数据
    $insertSql = "INSERT INTO books (title, author, isbn, publisher, category_id, price, stock, status, create_time) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $insertStmt = $pdo->prepare($insertSql);
    $result = $insertStmt->execute([
        $title, 
        $author, 
        $isbn, 
        $publisher, 
        $category_id, 
        $price, 
        $stock, 
        $status
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '图书添加成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '添加失败，请重试']);
    }
    
} catch (PDOException $e) {
    // 记录错误日志（实际项目中应该记录到文件或日志系统）
    error_log('添加图书失败: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '数据库操作失败']);
}
?>
