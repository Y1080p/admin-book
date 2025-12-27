<?php
header('Content-Type: application/json; charset=utf-8');

// CORS配置
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ['http://localhost:3005', 'http://localhost:3007', 'http://localhost:3000', 'http://127.0.0.1:3007'])) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 3600');
}

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Session配置 - 与api.php完全一致
ini_set('session.save_path', 'E:\phpstudy_pro\Extensions\tmp\tmp');

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => '' // 空字符串允许跨域请求发送cookie
]);

session_start();

error_log("=== TEST SESSION API ===");
error_log("Session ID: " . session_id());
error_log("Session Data: " . json_encode($_SESSION));
error_log("Cookies: " . json_encode($_COOKIE));

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // 设置session
    $input = json_decode(file_get_contents('php://input'), true);
    $_SESSION['test_value'] = time();
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = 'api_test_user';

    error_log("Session set - data: " . json_encode($_SESSION));

    echo json_encode([
        'success' => true,
        'message' => 'Session set successfully',
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]);
} else {
    // 获取session
    echo json_encode([
        'success' => true,
        'message' => 'Session retrieved',
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'has_data' => !empty($_SESSION)
    ]);
}
