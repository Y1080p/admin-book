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

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, OPTIONS'); // 登出通常用GET
    header('Access-Control-Allow-Headers: Content-Type');
}

// 3. 设置响应格式（可选，登出后跳转可保留）
header('Content-Type: text/html; charset=utf-8');

// 4. 原有session逻辑（移到CORS之后）
session_start();

// 5. 原有登出业务逻辑（保留）
// 清除所有session设置
$_SESSION = array();

// 删除session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁session
session_destroy();

// 重定向到登录页面
header('Location: login.php');
exit();
?>
