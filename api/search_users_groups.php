<?php
// 搜索用户和群聊的API文件
$allowedOrigins = ['http://localhost:3005', 'http://127.0.0.1:3005'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$keyword = $_GET['keyword'] ?? '';

if (empty($keyword)) {
    echo json_encode(['users' => [], 'groups' => []]);
    exit;
}

try {
    $pdo = getPDOConnection();
    $userId = $_SESSION['user_id'];
    
    $users = [];
    $groups = [];
    
    // 检查users表是否存在
    $usersTableExists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($usersTableExists) {
        try {
            // 检查users表是否有name字段
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $hasName = in_array('name', $columns);
            $nameCondition = $hasName ? "OR name LIKE ?" : "";
            $params = $hasName ? ["%$keyword%", "%$keyword%", $userId] : ["%$keyword%", $userId];
            
            // 搜索用户（排除当前用户自己）
            $userSql = "SELECT id, username, " . ($hasName ? "name" : "username as name") . " FROM users WHERE username LIKE ? $nameCondition AND id != ?";
            $userStmt = $pdo->prepare($userSql);
            $userStmt->execute($params);
            $users = $userStmt->fetchAll();
        } catch (Exception $e) {
            // 如果users表查询失败，继续执行但不返回错误
            error_log("搜索用户失败: " . $e->getMessage());
        }
    }
    
    // 检查chat_groups表是否存在
    $groupsTableExists = $pdo->query("SHOW TABLES LIKE 'chat_groups'")->fetch();
    if ($groupsTableExists) {
        try {
            // 检查表结构，确定正确的字段名
            $stmt = $pdo->query("DESCRIBE chat_groups");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $hasDescription = in_array('group_description', $columns);
            $descriptionField = $hasDescription ? 'group_description' : 'description';
            
            // 检查description字段是否存在
            $hasDescriptionField = in_array($descriptionField, $columns);
            $descriptionCondition = $hasDescriptionField ? "OR $descriptionField LIKE ?" : "";
            $params = $hasDescriptionField ? ["%$keyword%", "%$keyword%"] : ["%$keyword%"];
            
            // 搜索群聊
            $groupSql = "SELECT id, group_name, " . ($hasDescriptionField ? $descriptionField : "'' as $descriptionField") . ", group_owner_id FROM chat_groups WHERE group_name LIKE ? $descriptionCondition";
            $groupStmt = $pdo->prepare($groupSql);
            $groupStmt->execute($params);
            $groups = $groupStmt->fetchAll();
        } catch (Exception $e) {
            // 如果chat_groups表查询失败，继续执行但不返回错误
            error_log("搜索群聊失败: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'users' => $users,
        'groups' => $groups,
        'message' => '搜索完成'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '搜索失败: ' . $e->getMessage()]);
}
?>