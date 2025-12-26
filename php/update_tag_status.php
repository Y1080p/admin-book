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
    header('Access-Control-Allow-Methods: POST, OPTIONS'); // 标签状态修改用POST更安全
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 统一设置JSON响应格式（前置配置，避免格式混乱）
header('Content-Type: application/json; charset=utf-8');

// 5. 启动Session（必须在CORS之后，避免header报错）
session_start();

// 6. 原有登录检查逻辑（保留核心，补充401状态码）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // 401=未授权，前端可快速区分登录问题
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit();
}

// 7. 原有权限检查逻辑（保留核心，补充403状态码）
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403); // 403=禁止访问，区分权限问题
    echo json_encode(['success' => false, 'message' => '无权限操作']);
    exit();
}

// 8. 引入数据库连接（原有逻辑完全保留）
require_once '../SQL Connection/db_connect.php';

// 获取POST参数
$tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// 验证参数
if ($tag_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的标签ID']);
    exit();
}

if ($status !== 0 && $status !== 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的状态值']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 更新标签状态
    $sql = "UPDATE tags SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$status, $tag_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'newStatus' => $status]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '更新失败，标签可能不存在']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>
