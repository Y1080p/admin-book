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

// 获取表单数据
$tagId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$color = isset($_POST['color']) ? trim($_POST['color']) : '';
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// 验证数据
if ($tagId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的标签ID']);
    exit();
}

if (empty($name) || empty($color)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请填写完整的标签信息']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查标签是否存在
    $checkSql = "SELECT id FROM tags WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$tagId]);
    
    if (!$checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '标签不存在']);
        exit();
    }
    
    // 检查标签名称是否已存在（排除当前标签）
    $checkNameSql = "SELECT id FROM tags WHERE name = ? AND id != ?";
    $checkNameStmt = $pdo->prepare($checkNameSql);
    $checkNameStmt->execute([$name, $tagId]);
    
    if ($checkNameStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '标签名称已存在']);
        exit();
    }
    
    // 更新标签信息
    $updateSql = "UPDATE tags SET name = ?, color = ?, status = ?, update_time = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$name, $color, $status, $tagId]);
    
    if ($updateStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '标签修改成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '标签信息未发生变化']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>