<?php
// 启用错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS 配置
$allowedOrigins = [
    'http://localhost:3005',
    'http://localhost:3007',
    'http://localhost:3000',
    'https://stunning-biscochitos-49d12b.netlify.app'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 3600');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'SQL Connection/db_connect.php';

// 配置 session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
session_set_cookie_params([
    'lifetime' => 86400, // 24小时
    'path' => '/',
    'domain' => '', // 空字符串表示当前域名
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'lax'
]);

session_start();

// 路由处理 - 健壮版本
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// 调试信息
// error_log("REQUEST_URI: " . $request_uri);
// error_log("SCRIPT_NAME: " . $script_name);

// 尝试多种路径解析方法
$path_segments = [];

// 方法1: 使用PATH_INFO
if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $path_segments = array_values(array_filter(explode('/', trim($_SERVER['PATH_INFO'], '/'))));
}

// 方法2: 解析REQUEST_URI
if (empty($path_segments)) {
    $parsed_url = parse_url($request_uri);
    $path = $parsed_url['path'] ?? '';
    
    // 移除可能的路径前缀
    $prefixes = [
        '/client-book-vue/api',
        '/client-book-vue/api/index.php',
        '/api'
    ];
    
    foreach ($prefixes as $prefix) {
        if (strpos($path, $prefix) === 0) {
            $path = substr($path, strlen($prefix));
            break;
        }
    }
    
    $path_segments = array_values(array_filter(explode('/', trim($path, '/'))));
}

// 方法3: 如果仍然为空，使用默认路由
if (empty($path_segments)) {
    // 检查是否有查询参数指定路由
    if (isset($_GET['endpoint'])) {
        $path_segments = explode('/', $_GET['endpoint']);
    }
    // 支持 path 参数（从 .htaccess 重写而来）
    else if (isset($_GET['path'])) {
        $path_segments = explode('/', $_GET['path']);
    }
    // 支持 action 参数（用于 GET 请求）
    else if (isset($_GET['action'])) {
        $path_segments = explode('/', $_GET['action']);
    }
}

// 根据路径路由到不同的处理函数
$endpoint = $path_segments[0] ?? '';

// 调试信息
error_log("REQUEST_URI: " . $request_uri);
error_log("Path segments: " . json_encode($path_segments));
error_log("Endpoint: " . $endpoint);

switch ($endpoint) {
    case 'auth':
        handleAuth($path_segments);
        break;
    case 'books':
        handleBooks($path_segments);
        break;
    case 'categories':
        handleCategories();
        break;
    case 'user':
        handleUser($path_segments);
        break;
    case 'cart':
        handleCart($path_segments);
        break;
    case 'wishlist':
        handleWishlist($path_segments);
        break;
    case 'orders':
        handleOrders($path_segments);
        break;
    case 'chat':
        handleChat($path_segments);
        break;
    case 'search':
        // 根据 type 参数决定搜索类型
        $type = $_GET['type'] ?? 'users';
        if ($type === 'users') {
            searchUsers();
        } else if ($type === 'groups') {
            searchGroups();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid search type']);
        }
        break;
    case 'friend-request':
        sendFriendRequest();
        break;
    case 'requests':
        handleRequests($path_segments);
        break;
    case 'friends':
        handleFriends($path_segments);
        break;
    case 'check-session':
        http_response_code(200);
        echo json_encode([
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'session_id' => session_id()
        ]);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        break;
}

// 辅助函数：转换图片路径为线上 URL
function convertImageUrl($imagePath) {
    if (empty($imagePath)) {
        return 'https://admin-book-1nbo.onrender.com/image/book-icon.png';
    }

    // 如果已经是完整 URL，直接返回
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath;
    }

    // 转换相对路径为完整 URL
    $imagePath = ltrim($imagePath, './');
    $imagePath = ltrim($imagePath, '../');

    return 'https://admin-book-1nbo.onrender.com/' . $imagePath;
}

// 辅助函数：处理图书数据中的图片路径
function processBookImages(&$books) {
    foreach ($books as &$book) {
        if (isset($book['cover_image'])) {
            $book['cover_image'] = convertImageUrl($book['cover_image']);
        }
    }
}

// 认证相关接口
function handleAuth($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case 'check':
            checkLoginStatus();
            break;
        case 'login':
            login();
            break;
        case 'logout':
            logout();
            break;
        case 'register':
            register();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Auth endpoint not found']);
            break;
    }
}

// 图书相关接口
function handleBooks($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case '':
        case 'list':
            getBooks();
            break;
        case 'new':
            getNewBooks();
            break;
        case 'bestsellers':
            getBestsellers();
            break;
        default:
            // 检查是否是数字ID
            if (is_numeric($action)) {
                getBookDetail($action);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Books endpoint not found']);
            }
            break;
    }
}

// 分类接口
function handleCategories() {
    getCategories();
}

// 用户接口
function handleUser($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case 'profile':
            getUserProfile();
            break;
        case 'addresses':
            $subAction = $segments[2] ?? '';
            switch ($subAction) {
                case '':
                case 'list':
                    getUserAddresses();
                    break;
                case 'add':
                    addAddress();
                    break;
                case 'update':
                    $addressId = $segments[3] ?? 0;
                    updateAddress($addressId);
                    break;
                case 'delete':
                    $addressId = $segments[3] ?? 0;
                    deleteAddress($addressId);
                    break;
                case 'set-default':
                    $addressId = $segments[3] ?? 0;
                    setDefaultAddress($addressId);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Address endpoint not found']);
                    break;
            }
            break;
        case 'update-profile':
            updateProfile();
            break;
        case 'verify-password':
            verifyCurrentPassword();
            break;
        case 'update-password':
            updatePassword();
            break;
        case 'settings':
            handleUserSettings();
            break;
        case 'online-status-visibility':
            handleOnlineStatusVisibility();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'User endpoint not found']);
            break;
    }
}

// 购物车接口
function handleCart($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case '':
        case 'list':
            getCart();
            break;
        case 'add':
            addToCart();
            break;
        case 'remove':
            removeFromCart();
            break;
        case 'update':
            updateCartQuantity();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Cart endpoint not found']);
            break;
    }
}

// 收藏接口
function handleWishlist($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case '':
        case 'list':
            getWishlist();
            break;
        case 'toggle':
            toggleWishlist();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Wishlist endpoint not found']);
            break;
    }
}

// 检查登录状态
function checkLoginStatus() {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}

// 用户登录
function login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // 支持明文密码和加密密码
        $passwordMatch = false;
        if (substr($user['password'], 0, 1) === '$') {
            // 加密密码
            $passwordMatch = password_verify($password, $user['password']);
        } else {
            // 明文密码
            $passwordMatch = ($password === $user['password']);
        }
        
        if ($user && $passwordMatch) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // 更新用户在线状态为在线
            try {
                $updateStmt = $pdo->prepare("UPDATE users SET online_status = 'online', login_time = NOW(), last_active_time = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
            } catch (Exception $e) {
                error_log("Failed to update user online status on login: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => '登录成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '登录失败: ' . $e->getMessage()]);
    }
}

// 用户退出
function logout() {
    // 在销毁session前更新用户在线状态
    if (isset($_SESSION['user_id'])) {
        try {
            $pdo = getPDOConnection();
            $updateStmt = $pdo->prepare("UPDATE users SET online_status = 'offline', logout_time = NOW(), last_active_time = NOW() WHERE id = ?");
            $updateStmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Failed to update user online status on logout: " . $e->getMessage());
        }
    }
    
    session_destroy();
    echo json_encode(['success' => true]);
}

// 用户注册
function register() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '请填写完整信息']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '用户名已存在']);
            return;
        }
        
        // 检查邮箱是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '邮箱已存在']);
            return;
        }
        
        // 创建新用户
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, create_time) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $hashed_password]);
        
        echo json_encode(['success' => true, 'message' => '注册成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '注册失败: ' . $e->getMessage()]);
    }
}

// 获取图书列表
function getBooks() {
    try {
        $pdo = getPDOConnection();
        
        // 获取查询参数
        $category_id = $_GET['category_id'] ?? '';
        $title = $_GET['title'] ?? '';
        $author = $_GET['author'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = 12;
        $offset = ($page - 1) * $per_page;
        
        // 构建SQL查询
        $sql = "SELECT b.*, c.name as category_name FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.status = 1";
        
        $params = [];
        
        if (!empty($category_id)) {
            $sql .= " AND b.category_id = ?";
            $params[] = intval($category_id);
        }
        
        if (!empty($title)) {
            $sql .= " AND b.title LIKE ?";
            $params[] = "%$title%";
        }
        
        if (!empty($author)) {
            $sql .= " AND b.author LIKE ?";
            $params[] = "%$author%";
        }
        
        $sql .= " ORDER BY b.create_time DESC LIMIT $offset, $per_page";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll();
        
        // 确保数字字段是正确的类型
        foreach ($books as &$book) {
            $book['price'] = floatval($book['price']);
            $book['stock'] = intval($book['stock']);
            $book['category_id'] = intval($book['category_id']);
        }

        // 处理图片路径
        processBookImages($books);
        
        // 获取总数
        $count_sql = "SELECT COUNT(*) FROM books b WHERE b.status = 1";
        if (!empty($category_id)) {
            $count_sql .= " AND b.category_id = " . intval($category_id);
        }
        
        $count_stmt = $pdo->query($count_sql);
        $total_count = $count_stmt->fetchColumn();
        $total_pages = ceil($total_count / $per_page);
        
        echo json_encode([
            'books' => $books,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'current_page' => $page
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取图书失败: ' . $e->getMessage()]);
    }
}

// 获取新书推荐
function getNewBooks() {
    try {
        $pdo = getPDOConnection();
        $sql = "SELECT b.*, c.name as category_name FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.status = 1 
                ORDER BY b.create_time DESC LIMIT 12";
        $books = $pdo->query($sql)->fetchAll();
        
        // 确保数字字段是正确的类型
        foreach ($books as &$book) {
            $book['price'] = floatval($book['price']);
            $book['stock'] = intval($book['stock']);
            $book['category_id'] = intval($book['category_id']);
        }

        // 处理图片路径
        processBookImages($books);
        
        echo json_encode($books);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取新书失败: ' . $e->getMessage()]);
    }
}

// 获取畅销排行
function getBestsellers() {
    try {
        $pdo = getPDOConnection();
        
        // 计算一周前的日期
        $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        // 调试信息：输出统计条件
        error_log("畅销排行统计条件: 一周前 = $oneWeekAgo");
        
        $sql = "SELECT b.*, c.name as category_name, 
                       COUNT(oi.id) as order_count
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                LEFT JOIN order_items oi ON b.id = oi.book_id
                LEFT JOIN orders o ON oi.order_id = o.id 
                WHERE b.status = 1 
                AND o.create_time >= ? 
                AND o.status != 'cancelled'
                GROUP BY b.id
                ORDER BY order_count DESC, b.create_time DESC 
                LIMIT 12";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$oneWeekAgo]);
        $books = $stmt->fetchAll();
        
        // 调试信息：输出统计结果
        error_log("畅销排行统计结果: " . json_encode(array_map(function($book) {
            return [
                'book_id' => $book['id'],
                'title' => $book['title'],
                'order_count' => $book['order_count']
            ];
        }, $books)));
        
        // 确保数字字段是正确的类型
        foreach ($books as &$book) {
            $book['price'] = floatval($book['price']);
            $book['stock'] = intval($book['stock']);
            $book['category_id'] = intval($book['category_id']);
            $book['order_count'] = intval($book['order_count']);
        }

        // 处理图片路径
        processBookImages($books);

        echo json_encode($books);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取畅销书失败: ' . $e->getMessage()]);
    }
}

// 获取图书详情
function getBookDetail($bookId) {
    try {
        $pdo = getPDOConnection();
        
        $sql = "SELECT b.*, c.name as category_name FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE b.id = ? AND b.status = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        
        if ($book) {
            // 确保数字字段是正确的类型
            $book['price'] = floatval($book['price']);
            $book['stock'] = intval($book['stock']);
            $book['category_id'] = intval($book['category_id']);

            // 处理图片路径
            if (isset($book['cover_image'])) {
                $book['cover_image'] = convertImageUrl($book['cover_image']);
            }

            echo json_encode([
                'success' => true,
                'book' => $book
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '图书不存在'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '获取图书详情失败: ' . $e->getMessage()]);
    }
}

// 获取分类列表
function getCategories() {
    try {
        $pdo = getPDOConnection();
        $sql = "SELECT c.*, COUNT(b.id) as book_count 
                FROM categories c 
                LEFT JOIN books b ON c.id = b.category_id AND b.status = 1
                WHERE c.status = 1 
                GROUP BY c.id 
                ORDER BY c.name";
        $categories = $pdo->query($sql)->fetchAll();
        
        echo json_encode($categories);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取分类失败: ' . $e->getMessage()]);
    }
}

// 获取用户信息
function getUserProfile() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '未登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, create_time FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => '用户不存在']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取用户信息失败: ' . $e->getMessage()]);
    }
}

// 修改个人信息
function updateProfile() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        
        if (empty($username) || empty($email)) {
            echo json_encode(['success' => false, 'message' => '用户名和邮箱不能为空']);
            return;
        }
        
        // 检查邮箱是否已被其他用户使用
        $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmailStmt->execute([$email, $_SESSION['user_id']]);
        if ($checkEmailStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '该邮箱已被其他用户使用']);
            return;
        }
        
        // 更新用户信息
        $updateStmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $updateStmt->execute([$username, $email, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => '个人信息更新成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '更新个人信息失败: ' . $e->getMessage()]);
    }
}

// 验证当前密码
function verifyCurrentPassword() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $currentPassword = $input['currentPassword'] ?? '';
        
        if (empty($currentPassword)) {
            echo json_encode(['success' => false, 'message' => '请输入当前密码']);
            return;
        }
        
        // 验证当前密码
        $checkStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $checkStmt->execute([$_SESSION['user_id']]);
        $user = $checkStmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => '用户不存在']);
            return;
        }
        
        // 直接比较明文密码（因为你的数据库是明文存储的）
        if ($currentPassword !== $user['password']) {
            echo json_encode(['success' => false, 'message' => '当前密码错误']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => '密码验证成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '密码验证失败: ' . $e->getMessage()]);
    }
}

// 修改密码
function updatePassword() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $currentPassword = $input['currentPassword'] ?? '';
        $newPassword = $input['newPassword'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => '密码不能为空']);
            return;
        }
        
        // 验证当前密码
        $checkStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $checkStmt->execute([$_SESSION['user_id']]);
        $user = $checkStmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => '用户不存在']);
            return;
        }
        
        // 直接比较明文密码（因为你的数据库是明文存储的）
        if ($currentPassword !== $user['password']) {
            echo json_encode(['success' => false, 'message' => '当前密码错误']);
            return;
        }
        
        // 更新密码（直接存储明文，与数据库现有格式一致）
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$newPassword, $_SESSION['user_id']]);
        
        // 修改密码成功后清除会话，强制重新登录
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => '密码修改成功，请重新登录']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '修改密码失败: ' . $e->getMessage()]);
    }
}

// 添加到购物车
function addToCart() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $book_id = $input['book_id'] ?? 0;
    
    if (!$book_id) {
        http_response_code(400);
        echo json_encode(['error' => '图书ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查图书是否存在
        $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ? AND status = 1");
        $stmt->execute([$book_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '图书不存在']);
            return;
        }
        
        // 检查是否已在购物车
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$_SESSION['user_id'], $book_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => true, 'message' => '图书已在购物车中']);
        } else {
            // 添加到购物车
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, quantity, create_time) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$_SESSION['user_id'], $book_id]);
            echo json_encode(['success' => true, 'message' => '加入购物车成功']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '添加到购物车失败: ' . $e->getMessage()]);
    }
}

// 获取购物车
function getCart() {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        // 检查cart表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'cart'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['cart' => []]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT c.id, c.book_id, c.quantity, c.create_time,
                       b.title, b.author, b.price, b.cover_image, b.stock
                FROM cart c
                LEFT JOIN books b ON c.book_id = b.id
                WHERE c.user_id = ? AND b.status = 1
                ORDER BY c.create_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();
        
        // 确保数字字段是正确的类型
        foreach ($cartItems as &$item) {
            $item['price'] = floatval($item['price']);
            $item['quantity'] = intval($item['quantity']);
            $item['stock'] = intval($item['stock']);
            $item['book_id'] = intval($item['book_id']);
        }
        
        echo json_encode(['cart' => $cartItems]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取购物车失败: ' . $e->getMessage()]);
    }
}

// 从购物车移除
function removeFromCart() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $book_id = $input['book_id'] ?? 0;
    
    if (!$book_id) {
        http_response_code(400);
        echo json_encode(['error' => '图书ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$_SESSION['user_id'], $book_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => '已从购物车移除']);
        } else {
            echo json_encode(['success' => false, 'message' => '商品不在购物车中']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '移除失败: ' . $e->getMessage()]);
    }
}

// 更新购物车数量
function updateCartQuantity() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $book_id = $input['book_id'] ?? 0;
    $quantity = $input['quantity'] ?? 1;
    
    if (!$book_id || $quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => '参数错误']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查库存
        $stmt = $pdo->prepare("SELECT stock FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            echo json_encode(['success' => false, 'message' => '图书不存在']);
            return;
        }
        
        if ($quantity > $book['stock']) {
            echo json_encode(['success' => false, 'message' => '超出库存数量']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$quantity, $_SESSION['user_id'], $book_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => '数量已更新']);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失败']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '更新失败: ' . $e->getMessage()]);
    }
}

// 获取收藏列表
function getWishlist() {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        // 检查wishlist表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['wishlist' => []]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT w.book_id, w.create_time,
                       b.title, b.author, b.price, b.cover_image, b.stock
                FROM wishlist w
                LEFT JOIN books b ON w.book_id = b.id
                WHERE w.user_id = ? AND b.status = 1
                ORDER BY w.create_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $wishlistItems = $stmt->fetchAll();
        
        echo json_encode(['wishlist' => $wishlistItems]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取收藏列表失败: ' . $e->getMessage()]);
    }
}

// 切换收藏状态
function toggleWishlist() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $book_id = $input['book_id'] ?? 0;
    
    if (!$book_id) {
        http_response_code(400);
        echo json_encode(['error' => '图书ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查图书是否存在
        $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ? AND status = 1");
        $stmt->execute([$book_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '图书不存在']);
            return;
        }
        
        // 检查是否已收藏
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$_SESSION['user_id'], $book_id]);
        
        if ($stmt->fetch()) {
            // 取消收藏
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$_SESSION['user_id'], $book_id]);
            echo json_encode(['success' => true, 'message' => '已取消收藏']);
        } else {
            // 添加收藏
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, book_id, create_time) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $book_id]);
            echo json_encode(['success' => true, 'message' => '已添加到收藏']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '收藏操作失败: ' . $e->getMessage()]);
    }
}

// 订单相关接口
function handleOrders($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case '':
        case 'list':
            getOrders();
            break;
        case 'create':
            createOrder();
            break;
        default:
            // 检查是否是数字ID
            if (is_numeric($action)) {
                $subAction = $segments[2] ?? '';
                switch ($subAction) {
                    case '':
                        getOrderDetail($action);
                        break;
                    case 'detail':
                        getOrderDetail($action);
                        break;
                    case 'pay':
                        payOrder($action);
                        break;
                    case 'cancel':
                        cancelOrder($action);
                        break;
                    case 'confirm':
                        confirmReceipt($action);
                        break;
                    default:
                        http_response_code(404);
                        echo json_encode(['error' => 'Order endpoint not found']);
                        break;
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Orders endpoint not found']);
            }
            break;
    }
}

// 获取订单列表
function getOrders() {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        // 检查orders表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['orders' => []]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT o.*, 
                       COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ?
                GROUP BY o.id
                ORDER BY o.create_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();
        
        // 获取每个订单的商品详情
        foreach ($orders as &$order) {
            $itemSql = "SELECT oi.*, b.title, b.author, b.cover_image
                        FROM order_items oi
                        LEFT JOIN books b ON oi.book_id = b.id
                        WHERE oi.order_id = ?";
            $itemStmt = $pdo->prepare($itemSql);
            $itemStmt->execute([$order['id']]);
            $order['items'] = $itemStmt->fetchAll();
        }
        
        echo json_encode(['orders' => $orders]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取订单失败: ' . $e->getMessage()]);
    }
}

// 获取订单详情
function getOrderDetail($orderId) {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // 获取订单基本信息
        $sql = "SELECT o.*, ua.name as receiver_name, ua.phone as receiver_phone, 
                       ua.province, ua.city, ua.district, ua.detail as detail_address
                FROM orders o
                LEFT JOIN user_addresses ua ON o.address_info = ua.id
                WHERE o.id = ? AND o.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => '订单不存在']);
            return;
        }
        
        // 获取订单商品
        $itemSql = "SELECT oi.*, b.title, b.author, b.cover_image
                    FROM order_items oi
                    LEFT JOIN books b ON oi.book_id = b.id
                    WHERE oi.order_id = ?";
        $itemStmt = $pdo->prepare($itemSql);
        $itemStmt->execute([$orderId]);
        $order['items'] = $itemStmt->fetchAll();
        
        echo json_encode(['success' => true, 'order' => $order]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取订单详情失败: ' . $e->getMessage()]);
    }
}

// 创建订单
function createOrder() {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // 调试信息：输出接收到的数据
        error_log("创建订单接收到的数据: " . json_encode($input));
        
        $items = $input['items'] ?? [];
        $address = $input['address'] ?? null;
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => '订单不能为空']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // 生成订单号
        $orderNo = 'ORD' . date('YmdHis') . rand(1000, 9999);
        $userId = $_SESSION['user_id'];
        
        // 计算总金额
        $totalAmount = 0;
        foreach ($items as $item) {
            // 查询图书价格
            $bookSql = "SELECT price FROM books WHERE id = ?";
            $bookStmt = $pdo->prepare($bookSql);
            $bookStmt->execute([$item['book_id']]);
            $book = $bookStmt->fetch();
            
            if (!$book) {
                throw new Exception("图书不存在: ID " . $item['book_id']);
            }
            
            $totalAmount += $book['price'] * $item['quantity'];
        }
        
        // 获取地址ID
        $addressId = null;
        if ($address && isset($address['id'])) {
            // 如果前端传递了地址ID，使用传递的地址ID
            $addressId = $address['id'];
        } else {
            // 否则获取用户的默认地址
            $addressStmt = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND is_default = 1 AND status = 1 LIMIT 1");
            $addressStmt->execute([$userId]);
            $defaultAddress = $addressStmt->fetch();
            $addressId = $defaultAddress['id'] ?? null;
        }
        
        // 如果没有地址，返回错误
        if (!$addressId) {
            echo json_encode(['success' => false, 'message' => '请先设置收货地址']);
            return;
        }
        
        // 创建订单（设为待支付状态）
        $orderSql = "INSERT INTO orders (order_no, user_id, total_amount, status, address_info, create_time) 
                     VALUES (?, ?, ?, 'pending', ?, NOW())";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute([$orderNo, $userId, $totalAmount, $addressId]);
        $orderId = $pdo->lastInsertId();
        
        // 添加订单商品
        $itemSql = "INSERT INTO order_items (order_id, book_id, quantity, price, create_time) 
                    VALUES (?, ?, ?, ?, NOW())";
        $itemStmt = $pdo->prepare($itemSql);
        
        foreach ($items as $item) {
            // 再次查询图书价格，确保使用实际价格
            $bookSql = "SELECT price FROM books WHERE id = ?";
            $bookStmt = $pdo->prepare($bookSql);
            $bookStmt->execute([$item['book_id']]);
            $book = $bookStmt->fetch();
            
            if (!$book) {
                throw new Exception("图书不存在: ID " . $item['book_id']);
            }
            
            $itemStmt->execute([$orderId, $item['book_id'], $item['quantity'], $book['price']]);
            
            // 减少库存
            $updateStockSql = "UPDATE books SET stock = stock - ? WHERE id = ?";
            $updateStockStmt = $pdo->prepare($updateStockSql);
            $updateStockStmt->execute([$item['quantity'], $item['book_id']]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => '订单创建成功', 'order_id' => $orderId]);
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        
        // 调试信息：输出错误详情
        error_log("创建订单错误: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode(['error' => '创建订单失败: ' . $e->getMessage(), 'debug' => $e->getTraceAsString()]);
    }
}

// 支付订单
function payOrder($orderId) {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // 检查订单状态
        $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => '订单不存在']);
            return;
        }
        
        if ($order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => '订单状态不正确']);
            return;
        }
        
        // 更新订单状态
        $updateSql = "UPDATE orders SET status = 'paid', pay_time = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$orderId]);
        
        echo json_encode(['success' => true, 'message' => '支付成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '支付失败: ' . $e->getMessage()]);
    }
}

// 取消订单
function cancelOrder($orderId) {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // 检查订单状态
        $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => '订单不存在']);
            return;
        }
        
        if ($order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => '只能取消待付款的订单']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // 更新订单状态
        $updateSql = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$orderId]);
        
        // 恢复库存
        $itemSql = "SELECT book_id, quantity FROM order_items WHERE order_id = ?";
        $itemStmt = $pdo->prepare($itemSql);
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll();
        
        foreach ($items as $item) {
            $restoreStockSql = "UPDATE books SET stock = stock + ? WHERE id = ?";
            $restoreStockStmt = $pdo->prepare($restoreStockSql);
            $restoreStockStmt->execute([$item['quantity'], $item['book_id']]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => '订单已取消']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => '取消订单失败: ' . $e->getMessage()]);
    }
}

// 确认收货
function confirmReceipt($orderId) {
    try {
        $pdo = getPDOConnection();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先登录']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // 检查订单状态
        $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => '订单不存在']);
            return;
        }
        
        if ($order['status'] !== 'shipped') {
            echo json_encode(['success' => false, 'message' => '订单状态不正确']);
            return;
        }
        
        // 更新订单状态
        $updateSql = "UPDATE orders SET status = 'completed', complete_time = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$orderId]);
        
        echo json_encode(['success' => true, 'message' => '确认收货成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '确认收货失败: ' . $e->getMessage()]);
    }
}

// 聊天相关接口
function handleChat($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case 'groups':
            $subAction = $segments[2] ?? '';
            if (empty($subAction)) {
                // /chat/groups - 获取群聊列表
                getChatGroups();
            } else if (is_numeric($subAction)) {
                $groupId = $subAction;
                $subAction2 = $segments[3] ?? '';
                if ($subAction2 === 'messages') {
                    // /chat/groups/{id}/messages - 获取群聊消息
                    getGroupMessages($groupId);
                } else if ($subAction2 === 'send-message') {
                    // /chat/groups/{id}/send-message - 发送消息
                    sendGroupMessage($groupId);
                } else if ($subAction2 === 'members') {
                    // /chat/groups/{id}/members - 获取群成员列表
                    getGroupMembers($groupId);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Groups endpoint not found']);
                }
            } else if ($subAction === 'create') {
                // 创建群聊
                createChatGroup();
            } else if ($subAction === 'join-request') {
                sendGroupJoinRequest();
            } else if ($subAction === 'requests') {
                // 获取申请列表
                getRequestsList();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Groups endpoint not found']);
            }
            break;
        case 'search':
            // 直接访问 /api/search 进行搜索
            $type = $_GET['type'] ?? 'users';
            if ($type === 'users') {
                searchUsers();
            } else if ($type === 'groups') {
                searchGroups();
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid search type']);
            }
            break;
        case 'exit-group':
            exitGroup();
            break;
        case 'disband-group':
            disbandGroup();
            break;
        case 'friend-request':
            $subAction = $segments[2] ?? '';
            if ($subAction === 'send') {
                sendFriendRequest();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Friend request endpoint not found']);
            }
            break;
        case 'friends':
            $subAction = $segments[2] ?? '';
            if ($subAction === 'request') {
                sendFriendRequest();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Friends endpoint not found']);
            }
            break;
        case 'requests':
            $subAction = $segments[2] ?? '';
            if ($subAction === 'handle-friend') {
                handleFriendRequest();
            } else if ($subAction === 'handle-group') {
                handleGroupJoinRequest();
            } else if ($subAction === '') {
                // 直接访问 /api/requests 获取申请列表
                getRequestsList();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Requests endpoint not found']);
            }
            break;
        case '':
            // 直接访问 /api/requests 获取申请列表
            getRequestsList();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Chat endpoint not found']);
            break;
    }
}

// 申请相关接口
function handleRequests($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case 'handle-friend':
            handleFriendRequest();
            break;
        case 'handle-group':
            handleGroupJoinRequest();
            break;
        case 'check-sent':
            // 检查我发出的申请状态变化
            checkSentRequests();
            break;
        case '':
            // 直接访问 /api/requests 获取申请列表
            getRequestsList();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Requests endpoint not found']);
            break;
    }
}

// 搜索用户
function searchUsers() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $keyword = $_GET['keyword'] ?? '';
    
    if (empty($keyword)) {
        echo json_encode(['success' => true, 'users' => []]);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查users表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if (!$stmt->fetch()) {
            echo json_encode(['success' => true, 'users' => [], 'message' => '用户表不存在']);
            return;
        }
        
        // 搜索用户（排除当前用户）
        $currentUserId = $_SESSION['user_id'] ?? 0;
        $sql = "SELECT id, username, email FROM users WHERE username LIKE ? AND status = 1";
        
        // 移除当前用户过滤，显示所有匹配用户
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$keyword%"]);
        
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '搜索用户失败: ' . $e->getMessage()]);
    }
}

// 搜索群聊
function searchGroups() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $keyword = $_GET['keyword'] ?? '';
    
    if (empty($keyword)) {
        echo json_encode(['success' => true, 'groups' => []]);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查chat_groups表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'chat_groups'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['success' => true, 'groups' => [], 'message' => '群聊功能暂未启用']);
            return;
        }
        
        // 搜索群聊
        $sql = "SELECT id, group_name as name, group_owner_id, create_time, current_members as member_count 
                FROM chat_groups 
                WHERE group_name LIKE ? AND status = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$keyword%"]);
        $groups = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '搜索群聊失败: ' . $e->getMessage()]);
    }
}

// 获取申请列表
function getRequestsList() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        $requests = [];
        
        // 获取收到的好友申请
        $stmt = $pdo->query("SHOW TABLES LIKE 'friend_requests'");
        if ($stmt->fetch()) {
            // 收到的申请（待处理）
            $sql = "SELECT fr.id, fr.from_user_id, u.username as from_username, 'friend' as type, fr.status, fr.create_time 
                    FROM friend_requests fr 
                    JOIN users u ON fr.from_user_id = u.id 
                    WHERE fr.to_user_id = ? AND fr.status = 'pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $friendRequests = $stmt->fetchAll();
            
            foreach ($friendRequests as $request) {
                $requests[] = [
                    'id' => $request['id'],
                    'type' => 'friend',
                    'from_user_id' => $request['from_user_id'],
                    'from_username' => $request['from_username'],
                    'status' => $request['status'],
                    'create_time' => $request['create_time'],
                    'message' => '用户 ' . $request['from_username'] . ' 申请添加您为好友'
                ];
            }
        }
        
        // 获取收到的群聊加入申请（仅群主能收到）
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_join_requests'");
        if ($stmt->fetch()) {
            $sql = "SELECT gjr.id, gjr.group_id, gjr.user_id, u.username, cg.group_name, 'group' as type, gjr.status, gjr.create_time 
                    FROM group_join_requests gjr 
                    JOIN users u ON gjr.user_id = u.id 
                    JOIN chat_groups cg ON gjr.group_id = cg.id 
                    WHERE cg.group_owner_id = ? AND gjr.status = 'pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $groupRequests = $stmt->fetchAll();
            
            foreach ($groupRequests as $request) {
                $requests[] = [
                    'id' => $request['id'],
                    'type' => 'group',
                    'request_id' => $request['id'],
                    'group_id' => $request['group_id'],
                    'group_name' => $request['group_name'],
                    'user_id' => $request['user_id'],
                    'username' => $request['username'],
                    'status' => $request['status'],
                    'create_time' => $request['create_time'],
                    'message' => '用户 ' . $request['username'] . ' 申请加入群聊 ' . $request['group_name']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'requests' => $requests]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取申请列表失败: ' . $e->getMessage()]);
    }
}

// 检查我发出的申请状态变化和群成员变化（用于触发列表刷新）
function checkSentRequests() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        $acceptedRequests = [];
        $groupMembersChanges = [];
        
        // 获取我发出的已接受的好友申请
        $stmt = $pdo->query("SHOW TABLES LIKE 'friend_requests'");
        if ($stmt->fetch()) {
            $sql = "SELECT fr.id, fr.to_user_id, u.username as to_username, fr.status, fr.update_time, 'friend' as type
                    FROM friend_requests fr 
                    JOIN users u ON fr.to_user_id = u.id 
                    WHERE fr.from_user_id = ? AND fr.status = 'accepted'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $friendRequests = $stmt->fetchAll();
            $acceptedRequests = array_merge($acceptedRequests, $friendRequests);
        }
        
        // 获取我发出的已接受的群聊申请
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_join_requests'");
        if ($stmt->fetch()) {
            $sql = "SELECT gjr.id, gjr.group_id, cg.group_name, gjr.status, gjr.update_time, 'group' as type
                    FROM group_join_requests gjr 
                    JOIN chat_groups cg ON gjr.group_id = cg.id 
                    WHERE gjr.user_id = ? AND gjr.status = 'accepted'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $groupRequests = $stmt->fetchAll();
            $acceptedRequests = array_merge($acceptedRequests, $groupRequests);
        }
        
        // 检查群成员变化 - 获取我所在的群聊的最新成员数量
        $stmt = $pdo->query("SHOW TABLES LIKE 'chat_groups'");
        if ($stmt->fetch()) {
            // 获取我所在的群聊ID列表
            $sql = "SELECT DISTINCT group_id FROM group_members WHERE user_id = ?
                    UNION 
                    SELECT id FROM chat_groups WHERE group_owner_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $userId]);
            $myGroupIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            if (!empty($myGroupIds)) {
                // 获取这些群聊的当前成员数量
                $placeholders = str_repeat('?,', count($myGroupIds) - 1) . '?';
                $sql = "SELECT cg.id, cg.group_name, cg.current_members 
                        FROM chat_groups cg 
                        WHERE cg.id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($myGroupIds);
                $currentGroupMembers = $stmt->fetchAll();
                
                foreach ($currentGroupMembers as $group) {
                    $groupMembersChanges[] = [
                        'group_id' => $group['id'],
                        'group_name' => $group['group_name'],
                        'current_members' => intval($group['current_members']),
                        'type' => 'group_members_change'
                    ];
                }
            }
        }
        
        echo json_encode(['success' => true, 'acceptedRequests' => $acceptedRequests, 'groupMembersChanges' => $groupMembersChanges]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '检查状态变化失败: ' . $e->getMessage()]);
    }
}

// 处理好友申请
function handleFriendRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    // 从GET或POST请求中获取参数
    $requestId = null;
    $action = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $requestId = $_GET['request_id'] ?? 0;
        $action = $_GET['action'] ?? '';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = $input['request_id'] ?? 0;
        $action = $input['action'] ?? '';
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if (!$requestId || !in_array($action, ['accept', 'reject'])) {
        http_response_code(400);
        echo json_encode(['error' => '参数错误']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 调试信息
        error_log("处理好友申请 - 用户ID: $userId, 申请ID: $requestId, 动作: $action");
        
        // 检查申请是否存在且属于当前用户
        $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND to_user_id = ? AND status = 'pending'");
        $stmt->execute([$requestId, $userId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => '申请不存在']);
            return;
        }
        
        if ($action === 'accept') {
            // 更新申请状态
            $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$requestId]);
            
            // 添加好友关系（如果friends表存在）
            $stmt = $pdo->query("SHOW TABLES LIKE 'friends'");
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, create_time) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $request['from_user_id']]);
                // 双向好友关系 - 重新准备语句
                $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, create_time) VALUES (?, ?, NOW())");
                $stmt->execute([$request['from_user_id'], $userId]);
            }
            
            echo json_encode(['success' => true, 'message' => '好友申请已同意']);
        } else {
            // 拒绝申请
            $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$requestId]);
            echo json_encode(['success' => true, 'message' => '好友申请已拒绝']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        error_log("处理好友申请错误: " . $e->getMessage());
        echo json_encode(['error' => '处理好友申请失败: ' . $e->getMessage()]);
    }
}

// 处理群聊加入申请
function handleGroupJoinRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    // 从GET或POST请求中获取参数
    $requestId = null;
    $action = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $requestId = $_GET['request_id'] ?? 0;
        $action = $_GET['action'] ?? '';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = $input['request_id'] ?? 0;
        $action = $input['action'] ?? '';
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if (!$requestId || !in_array($action, ['accept', 'reject'])) {
        http_response_code(400);
        echo json_encode(['error' => '参数错误']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查申请是否存在且当前用户是群主
        $sql = "SELECT gjr.*, cg.group_owner_id 
                FROM group_join_requests gjr 
                JOIN chat_groups cg ON gjr.group_id = cg.id 
                WHERE gjr.id = ? AND gjr.status = 'pending' AND cg.group_owner_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestId, $userId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => '申请不存在或您不是群主']);
            return;
        }
        
        if ($action === 'accept') {
            // 更新申请状态
            $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'accepted', update_time = NOW() WHERE id = ?");
            $stmt->execute([$requestId]);
            
            // 添加群成员（如果group_members表存在）
            $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, join_time) VALUES (?, ?, NOW())");
                $stmt->execute([$request['group_id'], $request['user_id']]);
            }
            
            // 更新群成员数量
            $stmt = $pdo->prepare("UPDATE chat_groups SET current_members = current_members + 1 WHERE id = ?");
            $stmt->execute([$request['group_id']]);
            
            echo json_encode(['success' => true, 'message' => '入群申请已同意']);
        } else {
            // 拒绝申请
            $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'rejected', update_time = NOW() WHERE id = ?");
            $stmt->execute([$requestId]);
            echo json_encode(['success' => true, 'message' => '入群申请已拒绝']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '处理入群申请失败: ' . $e->getMessage()]);
    }
}

// 发送好友请求
function sendFriendRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $targetUserId = $input['target_user_id'] ?? 0;
    
    if (!$targetUserId) {
        http_response_code(400);
        echo json_encode(['error' => '目标用户ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查目标用户是否存在
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND status = 1");
        $stmt->execute([$targetUserId]);
        if (!$targetUser = $stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '目标用户不存在']);
            return;
        }
        
        // 检查目标用户是否允许好友申请
        $stmt = $pdo->prepare("SELECT settings FROM user_settings WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        $settingsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $allowFriendRequests = true;
        if ($settingsResult) {
            $settings = json_decode($settingsResult['settings'], true);
            $allowFriendRequests = isset($settings['allowFriendRequests']) ? (bool)$settings['allowFriendRequests'] : true;
        }
        
        if (!$allowFriendRequests) {
            http_response_code(403);
            echo json_encode(['error' => '该用户不允许接收好友申请']);
            return;
        }
        
        // 调试信息：开始好友关系检查
        error_log("DEBUG: 开始好友关系检查 - 用户ID: " . $_SESSION['user_id'] . ", 目标用户ID: " . $targetUserId);
        
        // 检查是否已经是好友（双向检查）
        try {
            $stmt = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$_SESSION['user_id'], $targetUserId, $targetUserId, $_SESSION['user_id']]);
            if ($friendRecord = $stmt->fetch()) {
                error_log("DEBUG: 找到好友关系记录 - 记录ID: " . $friendRecord['id']);
                
                // 获取目标用户的用户名用于友好提示
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$targetUserId]);
                $targetUser = $stmt->fetch();
                $username = $targetUser ? $targetUser['username'] : '对方';
                
                error_log("DEBUG: 返回友好提示 - 用户已有好友: " . $username);
                echo json_encode(['success' => false, 'message' => '你已有好友' . $username . '，可直接与对方进行聊天']);
                return;
            } else {
                error_log("DEBUG: 未找到好友关系记录");
            }
        } catch (Exception $e) {
            // 如果friends表不存在，忽略错误继续执行
            error_log("DEBUG: 好友关系检查失败（可能是表不存在）: " . $e->getMessage());
        }
        
        // 调试信息：检查friend_requests表
        error_log("DEBUG: 开始检查friend_requests表");
        
        // 检查friend_requests表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'friend_requests'");
        $friendRequestsTableExists = $stmt->fetch();
        
        if ($friendRequestsTableExists) {
            error_log("DEBUG: friend_requests表存在");
            
            // 检查是否已经发送过pending请求
            $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE from_user_id = ? AND to_user_id = ? AND status = 'pending'");
            $stmt->execute([$_SESSION['user_id'], $targetUserId]);
            if ($pendingRequest = $stmt->fetch()) {
                error_log("DEBUG: 找到pending请求 - 请求ID: " . $pendingRequest['id']);
                echo json_encode(['success' => true, 'message' => '好友请求已发送，请等待对方确认']);
                return;
            }
            
            // 检查是否已经存在已拒绝的请求，如果是则更新状态为pending
            $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE from_user_id = ? AND to_user_id = ? AND status = 'rejected'");
            $stmt->execute([$_SESSION['user_id'], $targetUserId]);
            if ($rejectedRequest = $stmt->fetch()) {
                error_log("DEBUG: 找到rejected请求 - 请求ID: " . $rejectedRequest['id']);
                try {
                    // 更新已拒绝的请求为pending状态
                    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'pending', create_time = NOW() WHERE id = ?");
                    $stmt->execute([$rejectedRequest['id']]);
                    error_log("DEBUG: 已更新rejected请求为pending");
                    echo json_encode(['success' => true, 'message' => '好友请求已重新发送，请等待对方确认']);
                    return;
                } catch (Exception $e) {
                    // 如果更新失败，可能是因为存在唯一约束，尝试创建新的请求
                    error_log("DEBUG: 更新拒绝的请求失败: " . $e->getMessage());
                }
            }
            
            // 检查是否存在accepted状态的请求（可能是双向好友关系但已删除）
            $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE from_user_id = ? AND to_user_id = ? AND status = 'accepted'");
            $stmt->execute([$_SESSION['user_id'], $targetUserId]);
            if ($acceptedRequest = $stmt->fetch()) {
                error_log("DEBUG: 找到accepted请求 - 请求ID: " . $acceptedRequest['id']);
                // 如果之前是已接受状态，现在可以重新发送申请
                try {
                    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'pending', create_time = NOW() WHERE id = ?");
                    $stmt->execute([$acceptedRequest['id']]);
                    error_log("DEBUG: 已更新accepted请求为pending");
                    echo json_encode(['success' => true, 'message' => '好友请求已重新发送，请等待对方确认']);
                    return;
                } catch (Exception $e) {
                    error_log("DEBUG: 更新已接受请求失败: " . $e->getMessage());
                }
            }
            
            error_log("DEBUG: 没有找到任何现有请求，创建新请求");
            
            // 创建新的好友请求
            try {
                $stmt = $pdo->prepare("INSERT INTO friend_requests (from_user_id, to_user_id, status, create_time) VALUES (?, ?, 'pending', NOW())");
                $stmt->execute([$_SESSION['user_id'], $targetUserId]);
                $requestId = $pdo->lastInsertId();
                error_log("DEBUG: 创建新请求成功 - 请求ID: " . $requestId);
            } catch (Exception $e) {
                // 如果插入失败，可能是因为唯一约束，尝试更新现有的请求
                error_log("DEBUG: 插入新请求失败: " . $e->getMessage());
                
                // 检查是否存在任何状态的请求
                $stmt = $pdo->prepare("SELECT id, status FROM friend_requests WHERE from_user_id = ? AND to_user_id = ?");
                $stmt->execute([$_SESSION['user_id'], $targetUserId]);
                if ($existingRequest = $stmt->fetch()) {
                    error_log("DEBUG: 找到现有请求 - 请求ID: " . $existingRequest['id'] . ", 状态: " . $existingRequest['status']);
                    // 更新现有请求为pending状态
                    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'pending', create_time = NOW() WHERE id = ?");
                    $stmt->execute([$existingRequest['id']]);
                    error_log("DEBUG: 已更新现有请求为pending");
                    echo json_encode(['success' => true, 'message' => '好友请求已重新发送，请等待对方确认']);
                    return;
                } else {
                    // 如果还是失败，抛出异常
                    throw $e;
                }
            }
        } else {
            error_log("DEBUG: friend_requests表不存在");
        }
        
        error_log("DEBUG: 发送好友请求成功");
        echo json_encode(['success' => true, 'message' => '好友请求发送成功']);
    } catch (Exception $e) {
        // 记录详细错误信息到日志
        error_log("发送好友请求失败 - 用户ID: " . $_SESSION['user_id'] . ", 目标用户ID: " . $targetUserId . ", 错误: " . $e->getMessage());
        error_log("错误堆栈: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode(['error' => '发送好友请求失败: ' . $e->getMessage()]);
    }
}

// 发送群聊加入请求
function sendGroupJoinRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $groupId = $input['group_id'] ?? 0;
    
    if (!$groupId) {
        http_response_code(400);
        echo json_encode(['error' => '群聊ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查群聊是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'chat_groups'");
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '群聊功能暂未启用']);
            return;
        }
        
        // 获取群聊信息，包括群主ID
        $stmt = $pdo->prepare("SELECT id, group_owner_id, group_name FROM chat_groups WHERE id = ? AND status = 1");
        $stmt->execute([$groupId]);
        if (!$group = $stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '群聊不存在']);
            return;
        }
        
        // 检查群主是否允许群聊申请
        $stmt = $pdo->prepare("SELECT settings FROM user_settings WHERE user_id = ?");
        $stmt->execute([$group['group_owner_id']]);
        $settingsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $allowGroupInvites = true;
        if ($settingsResult) {
            $settings = json_decode($settingsResult['settings'], true);
            $allowGroupInvites = isset($settings['allowGroupInvites']) ? (bool)$settings['allowGroupInvites'] : true;
        }
        
        if (!$allowGroupInvites) {
            http_response_code(403);
            echo json_encode(['error' => '该群聊已关闭群聊申请']);
            return;
        }
        
        // 检查是否已经是群成员（这里需要group_members表，暂时先简单处理）
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => true, 'message' => '您已经是该群成员']);
                return;
            }
        }
        
        // 检查group_join_requests表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_join_requests'");
        if ($stmt->fetch()) {
            // 检查是否已经发送过pending请求
            $stmt = $pdo->prepare("SELECT id FROM group_join_requests WHERE group_id = ? AND user_id = ? AND status = 'pending'");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if ($pendingRequest = $stmt->fetch()) {
                echo json_encode(['success' => true, 'message' => '入群请求已发送，请等待群主确认']);
                return;
            }
            
            // 检查是否已经存在已拒绝的请求，如果是则更新状态为pending
            $stmt = $pdo->prepare("SELECT id FROM group_join_requests WHERE group_id = ? AND user_id = ? AND status = 'rejected'");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if ($rejectedRequest = $stmt->fetch()) {
                try {
                    // 更新已拒绝的请求为pending状态
                    $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'pending', create_time = NOW() WHERE id = ?");
                    $stmt->execute([$rejectedRequest['id']]);
                    echo json_encode(['success' => true, 'message' => '入群请求已重新发送，请等待群主确认']);
                    return;
                } catch (Exception $e) {
                    // 如果更新失败，可能是因为存在唯一约束，尝试创建新的请求
                }
            }
            
            // 检查是否存在accepted状态的请求（可能是之前加入过但已退出）
            $stmt = $pdo->prepare("SELECT id FROM group_join_requests WHERE group_id = ? AND user_id = ? AND status = 'accepted'");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if ($acceptedRequest = $stmt->fetch()) {
                // 如果之前是已接受状态，现在可以重新发送申请
                try {
                    $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'pending', create_time = NOW() WHERE id = ?");
                    $stmt->execute([$acceptedRequest['id']]);
                    echo json_encode(['success' => true, 'message' => '入群请求已重新发送，请等待群主确认']);
                    return;
                } catch (Exception $e) {
                    // 如果更新失败，继续创建新请求
                }
            }
            
            // 创建新的入群请求
            try {
                $stmt = $pdo->prepare("INSERT INTO group_join_requests (group_id, user_id, status, create_time) VALUES (?, ?, 'pending', NOW())");
                $stmt->execute([$groupId, $_SESSION['user_id']]);
            } catch (Exception $e) {
                // 如果插入失败，可能是因为唯一约束，尝试更新现有的请求
                
                // 检查是否存在任何状态的请求
                $stmt = $pdo->prepare("SELECT id, status FROM group_join_requests WHERE group_id = ? AND user_id = ?");
                $stmt->execute([$groupId, $_SESSION['user_id']]);
                if ($existingRequest = $stmt->fetch()) {
                    // 更新现有请求为pending状态
                    $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'pending', create_time = NOW() WHERE id = ?");
                    $stmt->execute([$existingRequest['id']]);
                    echo json_encode(['success' => true, 'message' => '入群请求已重新发送，请等待群主确认']);
                    return;
                } else {
                    // 如果还是失败，抛出异常
                    throw $e;
                }
            }
        } else {
            // 如果表不存在，直接返回成功（模拟功能）
            echo json_encode(['success' => true, 'message' => '入群请求发送成功']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => '入群请求发送成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '发送入群请求失败: ' . $e->getMessage()]);
    }
}

// 获取群聊列表
function getChatGroups() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 检查chat_groups表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'chat_groups'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['success' => true, 'groups' => []]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // 检查group_members表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            // 表存在，查询用户所属的群聊，使用统一的成员计数逻辑
            $sql = "SELECT g.id, g.group_name as name, g.description, 
                    (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count, 
                    g.group_owner_id, g.create_time 
                    FROM chat_groups g
                    LEFT JOIN group_members gm ON g.id = gm.group_id
                    WHERE (g.group_owner_id = ? OR gm.user_id = ?) AND g.status = 1
                    GROUP BY g.id
                    ORDER BY g.create_time DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $userId]);
        } else {
            // 表不存在，查询用户是群主的群聊
            $sql = "SELECT id, group_name as name, description, 
                    (current_members + 1) as member_count, 
                    group_owner_id, create_time 
                    FROM chat_groups 
                    WHERE group_owner_id = ? AND status = 1 
                    ORDER BY create_time DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
        
        $groups = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (Exception $e) {
        // 发生错误时返回空数组而不是500错误
        echo json_encode(['success' => true, 'groups' => []]);
    }
}

// 获取群聊消息
function getGroupMessages($groupId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 首先检查群聊是否存在
        $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $groupExists = $stmt->fetch();
        
        if (!$groupExists) {
            http_response_code(404);
            echo json_encode(['error' => '该群聊已解散']);
            return;
        }
        
        // 检查用户是否在群聊中或者是群主
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            // 检查用户是否是群主或成员
            $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            $isMember = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            $isOwner = $stmt->fetch();
            
            if (!$isMember && !$isOwner) {
                http_response_code(403);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
        } else {
            // group_members表不存在，只检查是否是群主
            $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
        }
        
        // 检查group_messages表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_messages'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['success' => true, 'messages' => []]);
            return;
        }
        
        // 获取群聊消息
        $sql = "SELECT gm.*, u.username
                FROM group_messages gm
                LEFT JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = ?
                ORDER BY gm.create_time ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $messages = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取群聊消息失败: ' . $e->getMessage()]);
    }
}

// 发送群聊消息
function sendGroupMessage($groupId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $content = $input['content'] ?? '';
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => '消息内容不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 首先检查群聊是否存在
        $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $groupExists = $stmt->fetch();
        
        if (!$groupExists) {
            http_response_code(404);
            echo json_encode(['error' => '该群聊已解散']);
            return;
        }
        
        // 检查用户是否在群聊中或者是群主
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            // 检查用户是否是群主或成员
            $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            $isMember = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            $isOwner = $stmt->fetch();
            
            if (!$isMember && !$isOwner) {
                http_response_code(403);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
        } else {
            // group_members表不存在，只检查是否是群主
            $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
        }
        
        // 检查group_messages表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_messages'");
        if (!$stmt->fetch()) {
            // 表不存在，返回成功但模拟发送
            echo json_encode(['success' => true, 'message' => '消息发送成功（模拟）']);
            return;
        }
        
        // 发送消息
        $sql = "INSERT INTO group_messages (group_id, user_id, content, create_time) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $_SESSION['user_id'], $content]);
        
        echo json_encode(['success' => true, 'message' => '消息发送成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '发送消息失败: ' . $e->getMessage()]);
    }
}

// 好友相关接口
function handleFriends($segments) {
    $action = $segments[1] ?? '';
    
    switch ($action) {
        case 'list':
            getFriendsList();
            break;
        case 'online-status':
            checkFriendsOnlineStatus();
            break;
        case 'messages':
            $friendId = $segments[2] ?? 0;
            if ($friendId) {
                getPrivateMessages($friendId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => '好友ID不能为空']);
            }
            break;
        case 'send':
            sendPrivateMessage();
            break;
        case 'unread-count':
            $friendId = $segments[2] ?? 0;
            if ($friendId) {
                getUnreadMessageCount($friendId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => '好友ID不能为空']);
            }
            break;
        case 'delete':
            $friendId = $segments[2] ?? 0;
            if ($friendId) {
                deleteFriend($friendId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => '好友ID不能为空']);
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Friends endpoint not found']);
            break;
    }
}

// 获取好友列表
function getFriendsList() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查friends表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'friends'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['success' => true, 'friends' => []]);
            return;
        }
        
        // 获取好友列表 - 使用DISTINCT确保好友不重复
        $sql = "SELECT DISTINCT 
                    CASE 
                        WHEN f.user_id = ? THEN f.friend_id 
                        ELSE f.user_id 
                    END as friend_id, 
                    u.username, u.email, f.create_time 
                FROM friends f 
                JOIN users u ON (u.id = f.friend_id OR u.id = f.user_id) AND u.id != ?
                WHERE (f.user_id = ? OR f.friend_id = ?) AND u.status = 1
                ORDER BY f.create_time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId]);
        $friends = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'friends' => $friends]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取好友列表失败: ' . $e->getMessage()]);
    }
}

// 删除好友
function deleteFriend($friendId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查是否是好友关系
        $stmt = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => '您和该用户不是好友关系']);
            return;
        }
        
        // 删除好友关系（双向删除）
        $stmt = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        
        echo json_encode(['success' => true, 'message' => '好友删除成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '删除好友失败: ' . $e->getMessage()]);
    }
}

// 检查好友在线状态（使用新的在线状态系统）
function checkFriendsOnlineStatus() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 更新当前用户的最后活跃时间
        $updateStmt = $pdo->prepare("UPDATE users SET last_active_time = NOW() WHERE id = ?");
        $updateStmt->execute([$userId]);
        
        // 获取所有好友的在线状态（基于新的在线状态字段）
        $sql = "SELECT 
                    f.friend_id, 
                    u.username,
                    u.online_status,
                    u.last_active_time,
                    CASE 
                        WHEN u.online_status = 'online' AND TIMESTAMPDIFF(MINUTE, u.last_active_time, NOW()) <= 5 THEN 'online'
                        WHEN u.online_status = 'online' AND TIMESTAMPDIFF(MINUTE, u.last_active_time, NOW()) <= 15 THEN 'away'
                        ELSE 'offline'
                    END as actual_status
                FROM friends f 
                JOIN users u ON u.id = f.friend_id
                WHERE f.user_id = ? AND u.status = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $friendsStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 转换为键值对格式，方便前端使用
        $statusMap = [];
        foreach ($friendsStatus as $friend) {
            $statusMap[$friend['friend_id']] = $friend['actual_status'];
        }
        
        echo json_encode(['success' => true, 'friendsStatus' => $statusMap]);
    } catch (Exception $e) {
        error_log("Error in checkFriendsOnlineStatus: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// 获取私聊消息
function getPrivateMessages($friendId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查private_messages表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'private_messages'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['success' => true, 'messages' => []]);
            return;
        }
        
        // 检查是否是好友关系
        $stmt = $pdo->query("SHOW TABLES LIKE 'friends'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$userId, $friendId, $friendId, $userId]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => '您和该用户不是好友关系']);
                return;
            }
        }
        
        // 获取私聊消息
        $sql = "SELECT pm.*, u.username
                FROM private_messages pm
                LEFT JOIN users u ON pm.from_user_id = u.id
                WHERE (pm.from_user_id = ? AND pm.to_user_id = ?) OR (pm.from_user_id = ? AND pm.to_user_id = ?)
                ORDER BY pm.create_time ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        $messages = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取私聊消息失败: ' . $e->getMessage()]);
    }
}

// 发送私聊消息
function sendPrivateMessage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $toUserId = $input['to_user_id'] ?? 0;
    $content = $input['content'] ?? '';
    
    if (!$toUserId || empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => '参数错误']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查目标用户是否存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 1");
        $stmt->execute([$toUserId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '目标用户不存在']);
            return;
        }
        
        // 检查是否是好友关系
        $stmt = $pdo->query("SHOW TABLES LIKE 'friends'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$userId, $toUserId, $toUserId, $userId]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => '您和该用户不是好友关系']);
                return;
            }
        }
        
        // 检查private_messages表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'private_messages'");
        if (!$stmt->fetch()) {
            // 表不存在，返回成功但模拟发送
            echo json_encode(['success' => true, 'message' => '私聊消息发送成功（模拟）']);
            return;
        }
        
        // 发送私聊消息
        $sql = "INSERT INTO private_messages (from_user_id, to_user_id, content, is_read, create_time) VALUES (?, ?, ?, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $toUserId, $content]);
        
        echo json_encode(['success' => true, 'message' => '私聊消息发送成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '发送私聊消息失败: ' . $e->getMessage()]);
    }
}

// 获取未读消息计数（基于最后查看时间）
function getUnreadMessageCount($friendId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 获取最后查看时间（从请求参数中获取）
        $lastViewTime = $_GET['lastViewTime'] ?? null;
        
        error_log("获取未读消息计数 - 用户ID: $userId, 好友ID: $friendId, 最后查看时间: $lastViewTime");
        
        if ($lastViewTime) {
            // 基于最后查看时间统计未读消息：离开聊天界面后的新消息且未读
            $sql = "SELECT COUNT(*) as unread_count 
                    FROM private_messages 
                    WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0 AND create_time > ?";
            
            error_log("执行SQL: $sql");
            error_log("参数: from_user_id=$friendId, to_user_id=$userId, is_read=0, create_time>$lastViewTime");
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$friendId, $userId, $lastViewTime]);
        } else {
            // 如果没有最后查看时间，统计所有未读消息（默认行为：显示所有历史未读消息）
            $sql = "SELECT COUNT(*) as unread_count 
                    FROM private_messages 
                    WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0";
            
            error_log("执行SQL: $sql");
            error_log("参数: from_user_id=$friendId, to_user_id=$userId, is_read=0");
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$friendId, $userId]);
        }
        
        $result = $stmt->fetch();
        $unreadCount = $result['unread_count'] ?? 0;
        
        error_log("查询结果: unread_count = $unreadCount");
        
        echo json_encode(['success' => true, 'count' => $unreadCount]);
    } catch (Exception $e) {
        // 如果表不存在或其他错误，返回0
        error_log("获取未读消息计数异常: " . $e->getMessage());
        echo json_encode(['success' => true, 'count' => 0]);
    }
}

// 地址管理相关函数

// 获取用户地址列表
function getUserAddresses() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查user_addresses表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_addresses'");
        if (!$stmt->fetch()) {
            // 表不存在，返回空数组
            echo json_encode(['success' => true, 'addresses' => []]);
            return;
        }
        
        $sql = "SELECT * FROM user_addresses WHERE user_id = ? AND status = 1 ORDER BY is_default DESC, create_time DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $addresses = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'addresses' => $addresses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取地址列表失败: ' . $e->getMessage()]);
    }
}

// 添加地址
function addAddress() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 调试信息：输出接收到的参数
    error_log("addAddress 接收到的参数: " . json_encode($input));
    error_log("当前用户ID: " . ($_SESSION['user_id'] ?? '未设置'));
    
    // 验证必填字段
    $requiredFields = ['name', 'phone', 'province', 'city', 'district', 'detail'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            error_log("缺少必填字段: $field");
            http_response_code(400);
            echo json_encode(['error' => '请填写完整地址信息，缺少字段: ' . $field]);
            return;
        }
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查user_addresses表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_addresses'");
        if (!$stmt->fetch()) {
            http_response_code(500);
            echo json_encode(['error' => '地址表不存在，请先创建数据库表']);
            return;
        }
        
        // 如果是第一个地址，自动设为默认地址
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ? AND status = 1");
        $stmt->execute([$userId]);
        $addressCount = $stmt->fetchColumn();
        
        $isDefault = ($addressCount == 0) ? 1 : 0;
        
        // 插入新地址
        $sql = "INSERT INTO user_addresses (user_id, name, phone, province, city, district, detail, is_default, create_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $input['name'],
            $input['phone'],
            $input['province'],
            $input['city'],
            $input['district'],
            $input['detail'],
            $isDefault
        ]);
        
        $addressId = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'message' => '地址添加成功', 'address_id' => $addressId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '添加地址失败: ' . $e->getMessage()]);
    }
}

// 更新地址
function updateAddress($addressId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!$addressId || !is_numeric($addressId)) {
        http_response_code(400);
        echo json_encode(['error' => '地址ID无效']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 验证必填字段
    $requiredFields = ['name', 'phone', 'province', 'city', 'district', 'detail'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => '请填写完整地址信息']);
            return;
        }
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查地址是否存在且属于当前用户
        $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ? AND status = 1");
        $stmt->execute([$addressId, $userId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '地址不存在']);
            return;
        }
        
        // 更新地址
        $sql = "UPDATE user_addresses SET name = ?, phone = ?, province = ?, city = ?, district = ?, detail = ?, update_time = NOW() 
                WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['name'],
            $input['phone'],
            $input['province'],
            $input['city'],
            $input['district'],
            $input['detail'],
            $addressId,
            $userId
        ]);
        
        echo json_encode(['success' => true, 'message' => '地址更新成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '更新地址失败: ' . $e->getMessage()]);
    }
}

// 删除地址
function deleteAddress($addressId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!$addressId || !is_numeric($addressId)) {
        http_response_code(400);
        echo json_encode(['error' => '地址ID无效']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查地址是否存在且属于当前用户
        $stmt = $pdo->prepare("SELECT id, is_default FROM user_addresses WHERE id = ? AND user_id = ? AND status = 1");
        $stmt->execute([$addressId, $userId]);
        $address = $stmt->fetch();
        
        if (!$address) {
            http_response_code(404);
            echo json_encode(['error' => '地址不存在']);
            return;
        }
        
        // 如果是默认地址，需要先设置其他地址为默认
        if ($address['is_default']) {
            // 查找其他地址
            $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND id != ? AND status = 1 LIMIT 1");
            $stmt->execute([$userId, $addressId]);
            $otherAddress = $stmt->fetch();
            
            if ($otherAddress) {
                // 设置第一个找到的地址为默认地址
                $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
                $stmt->execute([$otherAddress['id']]);
            }
        }
        
        // 删除地址（更新状态为0）
        $sql = "UPDATE user_addresses SET status = 0, update_time = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$addressId, $userId]);
        
        echo json_encode(['success' => true, 'message' => '地址删除成功']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '删除地址失败: ' . $e->getMessage()]);
    }
}

// 设置默认地址
function setDefaultAddress($addressId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!$addressId || !is_numeric($addressId)) {
        http_response_code(400);
        echo json_encode(['error' => '地址ID无效']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查地址是否存在且属于当前用户
        $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ? AND status = 1");
        $stmt->execute([$addressId, $userId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => '地址不存在']);
            return;
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        // 取消所有地址的默认状态
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // 设置指定地址为默认地址
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1, update_time = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => '默认地址设置成功']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => '设置默认地址失败: ' . $e->getMessage()]);
    }
}

// 获取群成员列表
function getGroupMembers($groupId) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        
        // 首先检查群聊是否存在
        $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $groupExists = $stmt->fetch();
        
        if (!$groupExists) {
            http_response_code(404);
            echo json_encode(['error' => '该群聊已解散']);
            return;
        }
        
        // 检查用户是否在群聊中或者是群主
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            // 检查用户是否是群主或成员
            $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            $isMember = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            $isOwner = $stmt->fetch();
            
            if (!$isMember && !$isOwner) {
                http_response_code(403);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
        } else {
            // group_members表不存在，只检查是否是群主
            $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
        }
        
        // 获取群主信息
        $sql = "SELECT u.id, u.username, 'owner' as role, g.create_time 
                FROM chat_groups g 
                LEFT JOIN users u ON g.group_owner_id = u.id 
                WHERE g.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $owner = $stmt->fetch();
        
        // 获取群成员信息
        $members = [];
        
        // 检查group_members表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            $sql = "SELECT u.id, u.username, gm.role, gm.join_time as create_time 
                    FROM group_members gm 
                    LEFT JOIN users u ON gm.user_id = u.id 
                    WHERE gm.group_id = ? 
                    ORDER BY gm.join_time ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId]);
            $members = $stmt->fetchAll();
        }
        
        // 合并群主和成员列表，并去重处理
        $allMembers = [];
        
        // 先添加群主
        if ($owner) {
            $allMembers[] = $owner;
        }
        
        // 添加成员，但要排除群主（避免重复显示）
        foreach ($members as $member) {
            if ($member['id'] != $owner['id']) {
                $allMembers[] = $member;
            }
        }
        
        echo json_encode(['success' => true, 'members' => $allMembers]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取群成员列表失败: ' . $e->getMessage()]);
    }
}

// 创建群聊
function createChatGroup() {
    error_log("createChatGroup called with method: " . $_SERVER['REQUEST_METHOD']);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $groupName = $input['group_name'] ?? '';
    $description = $input['description'] ?? '';

    if (empty($groupName)) {
        http_response_code(400);
        echo json_encode(['error' => '群名称不能为空']);
        return;
    }

    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];

        // 插入新群聊
        $sql = "INSERT INTO chat_groups (group_name, group_owner_id, description, max_members, current_members, status, create_time, update_time) VALUES (?, ?, ?, 200, 1, 1, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupName, $userId, $description]);
        $groupId = $pdo->lastInsertId();

        // 检查group_members表是否存在，如果存在则添加群主为成员（但设置role为'owner'）
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            $memberSql = "INSERT INTO group_members (group_id, user_id, role, join_time) VALUES (?, ?, 'owner', NOW())";
            $memberStmt = $pdo->prepare($memberSql);
            $memberStmt->execute([$groupId, $userId]);
        }

        echo json_encode(['success' => true, 'message' => '群聊创建成功', 'group_id' => $groupId]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("createChatGroup error: " . $e->getMessage());
        echo json_encode(['error' => '创建群聊失败: ' . $e->getMessage()]);
    }
}

// 退出群聊
function exitGroup() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $groupId = $input['group_id'] ?? 0;
    
    if (!$groupId) {
        http_response_code(400);
        echo json_encode(['error' => '群聊ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查用户是否是群主
        $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
        $stmt->execute([$groupId, $userId]);
        $isOwner = $stmt->fetch();
        
        if ($isOwner) {
            http_response_code(400);
            echo json_encode(['error' => '群主不能退出群聊，请先解散群聊']);
            return;
        }
        
        // 检查用户是否是群成员
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            $isMember = $stmt->fetch();
            
            if (!$isMember) {
                http_response_code(400);
                echo json_encode(['error' => '您不在该群聊中']);
                return;
            }
            
        // 从群成员表中删除
        $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);
        
        // 更新群聊的当前成员数量
        $stmt = $pdo->prepare("UPDATE chat_groups SET current_members = current_members - 1 WHERE id = ?");
        $stmt->execute([$groupId]);
        
        // 清理该用户的群聊申请记录，允许重新申请
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_join_requests'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM group_join_requests WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
        }
        } else {
            // group_members表不存在，说明用户只是群主
            http_response_code(400);
            echo json_encode(['error' => '您不在该群聊中']);
            return;
        }
        
        echo json_encode(['success' => true, 'message' => '已成功退出群聊']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '退出群聊失败: ' . $e->getMessage()]);
    }
}

// 解散群聊（群主功能）
function disbandGroup() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录']);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $groupId = $input['group_id'] ?? 0;
    
    if (!$groupId) {
        http_response_code(400);
        echo json_encode(['error' => '群聊ID不能为空']);
        return;
    }
    
    try {
        $pdo = getPDOConnection();
        $userId = $_SESSION['user_id'];
        
        // 检查用户是否是群主
        $stmt = $pdo->prepare("SELECT id FROM chat_groups WHERE id = ? AND group_owner_id = ?");
        $stmt->execute([$groupId, $userId]);
        $isOwner = $stmt->fetch();
        
        if (!$isOwner) {
            http_response_code(403);
            echo json_encode(['error' => '只有群主才能解散群聊']);
            return;
        }
        
        // 删除群聊
        $stmt = $pdo->prepare("DELETE FROM chat_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        
        // 如果group_members表存在，删除所有群成员
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_members'");
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ?");
            $stmt->execute([$groupId]);
        }
        
        echo json_encode(['success' => true, 'message' => '群聊已解散']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '解散群聊失败: ' . $e->getMessage()]);
    }
}

// 处理用户设置
function handleUserSettings() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '未登录']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo = getPDOConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // 获取用户设置
            $stmt = $pdo->prepare("SELECT settings FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $settings = json_decode($result['settings'], true);
            } else {
                // 默认设置
                $settings = [
                    'showOnlineStatus' => true,
                    'allowFriendRequests' => true,
                    'allowGroupInvites' => true,
                    'notifyFriendMessages' => true,
                    'notifyGroupMessages' => true,
                    'notifyRequests' => true,
                    'desktopNotification' => false,
                    'theme' => 'light',
                    'compactMode' => false
                ];
            }
            
            echo json_encode(['success' => true, 'settings' => $settings]);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 更新用户设置
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => '无效的请求数据']);
                return;
            }
            
            // 验证并过滤设置数据
            $validSettings = [];
            $allowedSettings = [
                'showOnlineStatus' => 'boolean',
                'allowFriendRequests' => 'boolean',
                'allowGroupInvites' => 'boolean',
                'notifyFriendMessages' => 'boolean',
                'notifyGroupMessages' => 'boolean',
                'notifyRequests' => 'boolean',
                'desktopNotification' => 'boolean',
                'theme' => 'string',
                'compactMode' => 'boolean'
            ];
            
            foreach ($allowedSettings as $key => $type) {
                if (array_key_exists($key, $data)) {
                    if ($type === 'boolean') {
                        $validSettings[$key] = (bool)$data[$key];
                    } else {
                        $validSettings[$key] = $data[$key];
                    }
                }
            }
            
            if (empty($validSettings)) {
                http_response_code(400);
                echo json_encode(['error' => '没有有效的设置数据']);
                return;
            }
            
            // 检查是否已有设置记录
            $stmt = $pdo->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // 更新现有设置
                $stmt = $pdo->prepare("UPDATE user_settings SET settings = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([json_encode($validSettings), $userId]);
            } else {
                // 创建新设置记录
                $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, settings, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->execute([$userId, json_encode($validSettings)]);
            }
            
            // 如果有在线状态可见性设置，同时更新users表中的在线状态
            if (isset($validSettings['showOnlineStatus'])) {
                $isVisible = $validSettings['showOnlineStatus'];
                if (!$isVisible) {
                    $stmt = $pdo->prepare("UPDATE users SET online_status = 'offline', last_active_time = NOW() WHERE id = ?");
                    $stmt->execute([$userId]);
                } else {
                    // 如果设置为可见，恢复为在线状态
                    $stmt = $pdo->prepare("UPDATE users SET online_status = 'online', last_active_time = NOW() WHERE id = ?");
                    $stmt->execute([$userId]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => '设置已更新', 'settings' => $validSettings]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => '方法不允许']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '设置处理失败: ' . $e->getMessage()]);
    }
}

// 处理在线状态可见性
function handleOnlineStatusVisibility() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => '未登录']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo = getPDOConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 更新在线状态可见性
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['isVisible'])) {
                http_response_code(400);
                echo json_encode(['error' => '无效的请求数据']);
                return;
            }
            
            $isVisible = (bool)$data['isVisible'];
            
            // 更新用户设置表中的在线状态可见性
            $stmt = $pdo->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // 更新现有设置
                $currentSettings = $pdo->prepare("SELECT settings FROM user_settings WHERE user_id = ?");
                $currentSettings->execute([$userId]);
                $settingsResult = $currentSettings->fetch(PDO::FETCH_ASSOC);
                $settings = json_decode($settingsResult['settings'], true);
                $settings['showOnlineStatus'] = $isVisible;
                
                $stmt = $pdo->prepare("UPDATE user_settings SET settings = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([json_encode($settings), $userId]);
            } else {
                // 创建新设置记录
                $settings = ['showOnlineStatus' => $isVisible];
                $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, settings, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->execute([$userId, json_encode($settings)]);
            }
            
            // 更新users表中的在线状态
            if (!$isVisible) {
                $stmt = $pdo->prepare("UPDATE users SET online_status = 'offline', last_active_time = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
            } else {
                // 如果设置为可见，恢复为在线状态
                $stmt = $pdo->prepare("UPDATE users SET online_status = 'online', last_active_time = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
            }
            
            echo json_encode(['success' => true, 'message' => '在线状态可见性已更新', 'isVisible' => $isVisible]);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // 获取当前在线状态可见性设置
            $stmt = $pdo->prepare("SELECT settings FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $settings = json_decode($result['settings'], true);
                $isVisible = isset($settings['showOnlineStatus']) ? (bool)$settings['showOnlineStatus'] : true;
            } else {
                // 如果没有设置，默认为可见
                $isVisible = true;
            }
            
            // 同时检查在线状态表中的实际状态
            $stmt = $pdo->prepare("SELECT online_status FROM user_online_status WHERE username = (SELECT username FROM users WHERE id = ?)");
            $stmt->execute([$userId]);
            $statusResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $actualStatus = $statusResult ? $statusResult['online_status'] : 'offline';
            
            echo json_encode(['success' => true, 'isVisible' => $isVisible, 'actualStatus' => $actualStatus]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => '方法不允许']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '处理失败: ' . $e->getMessage()]);
    }
}
?>