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

// 3. 仅给信任域名返回CORS头（安全+支持Session传递）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true'); // 关键：没有这个Session会失效
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 适配AJAX常用请求
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置配置，避免格式混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有Session逻辑（必须移到CORS之后，避免header报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充401状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 = 未授权，前端可快速识别
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 获取图书ID数组
$bookIdsJson = isset($_POST['book_ids']) ? $_POST['book_ids'] : '';

if (empty($bookIdsJson)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请选择要删除的图书']);
    exit();
}

$bookIds = json_decode($bookIdsJson, true);

if (!is_array($bookIds) || empty($bookIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的图书ID数据']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 验证图书是否存在
    $placeholders = str_repeat('?,', count($bookIds) - 1) . '?';
    $checkSql = "SELECT id, title FROM books WHERE id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($bookIds);
    $existingBooks = $checkStmt->fetchAll();
    
    if (count($existingBooks) !== count($bookIds)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分图书不存在']);
        exit();
    }
    
    // 开始事务
    $pdo->beginTransaction();
    
    // 批量删除图书
    $deleteSql = "DELETE FROM books WHERE id IN ($placeholders)";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute($bookIds);
    
    $deletedCount = $deleteStmt->rowCount();
    
    // 提交事务
    $pdo->commit();
    
    if ($deletedCount > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '批量删除成功', 'deleted_count' => $deletedCount]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    // 回滚事务
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>
