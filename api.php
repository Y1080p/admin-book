<?php
// API 入口文件 - 直接访问此文件，不依赖 .htaccess
// 1. 优先处理 OPTIONS 预请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 安全的 CORS 配置
$allowedOrigins = [
    'http://localhost:3005',
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// 3. 统一响应格式
header('Content-Type: application/json; charset=utf-8');

// 4. 配置 session cookie 参数
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'lax'
]);

// 5. 引入数据库连接
require_once 'SQL Connection/db_connect.php';

// 获取 PDO 连接
try {
    $pdo = getPDOConnection();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '数据库连接失败: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// 6. 启动 session
session_start();

// 7. 统一响应函数
function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 8. 获取请求参数
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$path = isset($_GET['path']) ? $_GET['path'] : '';
$uri = $_SERVER['REQUEST_URI'] ?? '';

// 优先使用 path 参数（从 /api/* 重写而来），其次使用 action 参数
if (empty($action) && !empty($path)) {
    $action = $path;
}

// 支持直接访问：/api.php/books/list
if (empty($action)) {
    $urlPath = parse_url($uri, PHP_URL_PATH);
    $urlPath = str_replace('/api.php', '', $urlPath);
    $urlPath = ltrim($urlPath, '/');
    if (!empty($urlPath)) {
        $action = $urlPath;
    }
}

// 9. 处理不同的 API 请求

// 测试端点 - 不需要登录
if ($method === 'GET' && ($action === 'test' || $action === '')) {
    sendResponse(true, [
        'status' => 'OK',
        'message' => 'API 测试成功！',
        'server_info' => [
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'request_uri' => $uri,
            'action' => $action,
            'method' => $method,
            'origin' => $origin,
            'session_id' => session_id()
        ]
    ], 'API 运行正常');
}

// 健康检查
if ($method === 'GET' && $action === 'health') {
    sendResponse(true, [
        'status' => 'OK',
        'server_info' => [
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'request_uri' => $uri,
            'action' => $action,
            'method' => $method
        ]
    ], 'API 运行正常');
}

// 检查登录状态
if ($method === 'GET' && $action === 'auth/check') {
    if (isset($_SESSION['user_id'])) {
        sendResponse(true, [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ], '用户已登录');
    } else {
        sendResponse(false, null, '用户未登录', 401);
    }
}

// 登录接口
if ($method === 'POST' && $action === 'auth/login') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['username'], $input['password'])) {
            sendResponse(false, null, '缺少用户名或密码', 400);
        }

        $username = trim($input['username']);
        $password = trim($input['password']);

        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            sendResponse(false, null, '用户名或密码错误', 401);
        }

        if ($user['password'] !== $password) {
            sendResponse(false, null, '用户名或密码错误', 401);
        }

        if ($user['role'] === '用户') {
            sendResponse(false, null, '该账号无权限登录后台系统', 403);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        sendResponse(true, [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ], '登录成功');
    } catch (PDOException $e) {
        sendResponse(false, null, '登录失败: ' . $e->getMessage(), 500);
    }
}

// 登出接口
if ($method === 'POST' && $action === 'auth/logout') {
    session_unset();
    session_destroy();
    sendResponse(true, null, '登出成功');
}

// 获取图书列表
if ($method === 'GET' && $action === 'books/list') {
    try {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
        $title = isset($_GET['title']) ? $_GET['title'] : '';
        $author = isset($_GET['author']) ? $_GET['author'] : '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = isset($_GET['page_size']) ? intval($_GET['page_size']) : 10;

        $sql = "SELECT * FROM books WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (title LIKE :search OR author LIKE :search OR category LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($category)) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }

        if (!empty($category_id)) {
            $sql .= " AND category = :category_id";
            $params[':category_id'] = $category_id;
        }

        if (!empty($title)) {
            $sql .= " AND title LIKE :title";
            $params[':title'] = "%$title%";
        }

        if (!empty($author)) {
            $sql .= " AND author LIKE :author";
            $params[':author'] = "%$author%";
        }

        $countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        $offset = ($page - 1) * $pageSize;
        $sql .= " ORDER BY created_at DESC LIMIT :offset, :pageSize";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll();

        sendResponse(true, [
            'books' => $books,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ], '获取图书列表成功');
    } catch (PDOException $e) {
        sendResponse(false, null, '获取图书列表失败: ' . $e->getMessage(), 500);
    }
}

// 获取分类列表
if ($method === 'GET' && $action === 'categories') {
    try {
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY create_time DESC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $categories, '获取分类列表成功');
        } catch (PDOException $e) {
            $stmt = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sendResponse(true, $categories, '获取分类列表成功');
        }
    } catch (PDOException $e) {
        sendResponse(false, null, '获取分类列表失败: ' . $e->getMessage(), 500);
    }
}

// 默认响应
sendResponse(false, null, '无效的请求: ' . $action, 404);
?>
