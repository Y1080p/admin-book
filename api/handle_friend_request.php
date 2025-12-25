<?php
// 处理好友申请的独立API文件
$allowedOrigins = ['http://localhost:3005', 'http://127.0.0.1:3005'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../client-book/SQL Connection/db_connect.php';

// 配置 session
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

$input = json_decode(file_get_contents('php://input'), true);
$requestId = $input['request_id'] ?? 0;
$action = $input['action'] ?? ''; // 'accept' or 'reject'

if (!$requestId || !in_array($action, ['accept', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => '参数错误']);
    exit;
}

try {
    $pdo = getPDOConnection();
    $userId = $_SESSION['user_id'];

    // 检查申请是否存在且属于当前用户
    $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND to_user_id = ? AND status = 'pending'");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => '申请不存在']);
        exit;
    }

    if ($action === 'accept') {
        // 更新申请状态
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted', process_time = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);

        // 添加好友关系（如果friends表存在）
        $stmt = $pdo->query("SHOW TABLES LIKE 'friends'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, create_time) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $request['from_user_id']]);
            // 双向好友关系
            $stmt->execute([$request['from_user_id'], $userId]);
        }

        echo json_encode(['success' => true, 'message' => '好友申请已同意']);
    } else {
        // 拒绝申请
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'rejected', process_time = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);
        echo json_encode(['success' => true, 'message' => '好友申请已拒绝']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '处理好友申请失败: ' . $e->getMessage()]);
}
?>
