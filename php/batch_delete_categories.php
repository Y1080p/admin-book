<?php
// 1. 优先处理 OPTIONS 预请求（跨域 AJAX 必加，避免浏览器拦截）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置信任的跨域域名（本地开发 + 线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 替换为你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回 CORS 头（安全 + 支持 Session 传递）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true'); // 关键：无此配置 Session 会失效
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 适配 AJAX 常用请求
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置 JSON 响应格式（前置配置，避免格式混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有 Session 逻辑（必须移到 CORS 之后，避免 header 报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充 401 状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401 未授权，前端可快速识别状态
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 获取POST参数
$category_ids_json = isset($_POST['category_ids']) ? $_POST['category_ids'] : '[]';
$category_ids = json_decode($category_ids_json, true);

// 验证参数
if (!is_array($category_ids) || empty($category_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请选择要删除的分类']);
    exit();
}

// 验证分类ID格式
$valid_ids = [];
foreach ($category_ids as $id) {
    $id = intval($id);
    if ($id > 0) {
        $valid_ids[] = $id;
    }
}

if (empty($valid_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查分类是否存在
    $placeholders = str_repeat('?,', count($valid_ids) - 1) . '?';
    $checkSql = "SELECT id FROM categories WHERE id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($valid_ids);
    $existing_ids = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($existing_ids) != count($valid_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分分类不存在']);
        exit();
    }
    
    // 检查是否有图书关联这些分类
    $checkBooksSql = "SELECT DISTINCT category_id FROM books WHERE category_id IN ($placeholders) LIMIT 1";
    $checkBooksStmt = $pdo->prepare($checkBooksSql);
    $checkBooksStmt->execute($valid_ids);
    
    if ($checkBooksStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分分类下有关联的图书，无法删除']);
        exit();
    }
    
    // 开始事务
    $pdo->beginTransaction();
    
    try {
        // 批量删除分类
        $deleteSql = "DELETE FROM categories WHERE id IN ($placeholders)";
        $deleteStmt = $pdo->prepare($deleteSql);
        $result = $deleteStmt->execute($valid_ids);
        
        if ($result) {
            $pdo->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
        } else {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '批量删除失败']);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除过程中发生错误: ' . $e->getMessage()]);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>
