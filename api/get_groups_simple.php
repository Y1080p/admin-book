<?php
// 简化版的群聊列表API

// 1. 优先处理OPTIONS预请求（避免跨域预请求报错）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 2. 动态设置CORS头部（添加线上前端域名）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回CORS头
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET'); // 匹配该接口的请求方法
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// 4. 统一设置响应格式
header('Content-Type: application/json; charset=utf-8');

// 5. 引入数据库连接
require_once '../../client-book/SQL Connection/db_connect.php';

// 6. 配置并启动session（补充session_start()，否则session无法使用）
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'lax'
]);

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '请先登录']);
    exit;
}

try {
    $pdo = getPDOConnection();
    
    // 检查chat_groups表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'chat_groups'");
    if (!$stmt->fetch()) {
        // 表不存在，返回空数组
        echo json_encode(['success' => true, 'groups' => [], 'message' => '群聊功能暂未启用']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // 使用简单的查询，只获取基本字段
    $stmt = $pdo->prepare("SELECT id, group_name as name, create_time FROM chat_groups WHERE status = 1 ORDER BY create_time DESC");
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 为每个群组添加一个默认成员数
    foreach ($groups as &$group) {
        $group['member_count'] = 1; // 默认值
        $group['group_description'] = ''; // 默认值
    }
    
    echo json_encode(['success' => true, 'groups' => $groups]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '获取群聊列表失败: ' . $e->getMessage()]);
}
?>
