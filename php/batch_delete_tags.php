<?php
// 1. 处理跨域预请求（浏览器先发 OPTIONS 探测，必须优先处理）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置允许跨域的信任域名（本地开发+线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 替换为你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回跨域头（安全+支持Session传递）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true'); // 关键：无此配置Session会失效
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // 适配AJAX常用请求
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置避免后续输出混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 启动Session（必须在跨域头之后，否则报header错误）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充401状态码更规范）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401未授权，前端可通过状态码快速判断
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 7. 引入数据库连接（原有逻辑完全保留）
require_once '../SQL Connection/db_connect.php';

// 获取标签ID数组
$tagIdsJson = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : '[]';
$tagIds = json_decode($tagIdsJson, true);

// 验证数据
if (!is_array($tagIds) || empty($tagIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请选择要删除的标签']);
    exit();
}

// 过滤有效的标签ID
$validTagIds = array_filter($tagIds, function($id) {
    return is_numeric($id) && $id > 0;
});

if (empty($validTagIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的标签ID']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查标签是否存在
    $placeholders = str_repeat('?,', count($validTagIds) - 1) . '?';
    $checkSql = "SELECT id, name FROM tags WHERE id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($validTagIds);
    $existingTags = $checkStmt->fetchAll();
    
    if (count($existingTags) !== count($validTagIds)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分标签不存在']);
        exit();
    }
    
    // 批量删除标签
    $deleteSql = "DELETE FROM tags WHERE id IN ($placeholders)";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute($validTagIds);
    
    if ($result && $deleteStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '批量删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>
