<?php
// 1. 优先处理OPTIONS预请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置允许的跨域域名
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
header('Content-Type: application/json; charset=utf-8');

// 3. 启动session
session_start();

// 4. 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 获取标签ID
$tagId = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;

if ($tagId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的标签ID']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查标签是否存在
    $checkSql = "SELECT id, name FROM tags WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$tagId]);
    $tag = $checkStmt->fetch();
    
    if (!$tag) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '标签不存在']);
        exit();
    }
    
    // 删除标签
    $deleteSql = "DELETE FROM tags WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$tagId]);
    
    if ($deleteStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '标签删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>