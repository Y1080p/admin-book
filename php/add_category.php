<?php
// 1. 优先处理OPTIONS预请求（AJAX跨域必加，避免浏览器拦截预请求）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置信任的跨域域名（本地开发环境 + 线上前端域名）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 替换成你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回CORS头（安全规范，避免任意域名调用）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    // 适配AJAX接口的常用请求方法（GET/POST覆盖绝大多数场景）
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置配置，避免后续输出混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有session逻辑（必须移到CORS之后，避免header冲突报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，优化状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 新增401未授权状态码，前端更易识别
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 获取POST参数
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// 验证参数
if (empty($name)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
    exit();
}

if ($sort_order < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '排序值不能为负数']);
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
    
    // 检查分类名称是否已存在
    $checkSql = "SELECT id FROM categories WHERE name = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$name]);
    
    if ($checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类名称已存在']);
        exit();
    }
    
    // 插入新分类
    $insertSql = "INSERT INTO categories (name, description, sort_order, status, create_time) VALUES (?, ?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $result = $insertStmt->execute([$name, $description, $sort_order, $status]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '分类添加成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '添加失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>
