<?php
// 1. 处理OPTIONS预请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置跨域域名（信任本地+线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 检测是否为API请求
$isApiRequest = in_array($origin, $allowedOrigins);

if ($isApiRequest) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=utf-8');
}

// 4. 启动session
session_start();

// 5. 原有登出业务逻辑（保留）
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// 根据请求类型返回不同的响应
if ($isApiRequest) {
    echo json_encode(['success' => true, 'message' => '登出成功']);
} else {
    header('Location: login.php');
}
exit();
?>
