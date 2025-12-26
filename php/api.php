<?php
// 1. 优先处理OPTIONS预请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 安全的CORS配置（仅允许信任域名）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
// 统一允许的方法和头（覆盖核心接口的常用场景）
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 3. 统一响应格式
header('Content-Type: application/json; charset=utf-8');

// 4. 引入数据库连接
require_once '../SQL Connection/db_connect.php';

// 获取PDO连接
$pdo = getPDOConnection();

// 处理请求方法
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 统一响应函数
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

// 获取所有图书
if ($method === 'GET' && $action === 'get_books') {
    try {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        
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
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll();
        
        sendResponse(true, $books, '获取图书列表成功');
        
    } catch (PDOException $e) {
        sendResponse(false, null, '获取图书列表失败: ' . $e->getMessage(), 500);
    }
}

// 获取统计信息
if ($method === 'GET' && $action === 'get_stats') {
    try {
        // 总图书数量
        $stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
        $totalBooks = $stmt->fetch()['total_books'];
        
        // 作者数量
        $stmt = $pdo->query("SELECT COUNT(DISTINCT author) as total_authors FROM books");
        $totalAuthors = $stmt->fetch()['total_authors'];
        
        // 分类数量
        $stmt = $pdo->query("SELECT COUNT(DISTINCT category) as total_categories FROM books");
        $totalCategories = $stmt->fetch()['total_categories'];
        
        // 总价值
        $stmt = $pdo->query("SELECT SUM(price) as total_value FROM books");
        $totalValueData = $stmt->fetch();
        $totalValue = isset($totalValueData['total_value']) ? $totalValueData['total_value'] : 0;
        
        $stats = [
            'total_books' => $totalBooks,
            'total_authors' => $totalAuthors,
            'total_categories' => $totalCategories,
            'total_value' => $totalValue
        ];
        
        sendResponse(true, $stats, '获取统计信息成功');
        
    } catch (PDOException $e) {
        sendResponse(false, null, '获取统计信息失败: ' . $e->getMessage(), 500);
    }
}

// 获取所有分类
if ($method === 'GET' && $action === 'get_categories') {
    try {
        $stmt = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        sendResponse(true, $categories, '获取分类列表成功');
        
    } catch (PDOException $e) {
        sendResponse(false, null, '获取分类列表失败: ' . $e->getMessage(), 500);
    }
}

// 添加新图书
if ($method === 'POST' && $action === 'add_book') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['title'], $input['author'], $input['publish_year'], $input['price'], $input['category'])) {
            sendResponse(false, null, '缺少必要参数', 400);
        }
        
        // 验证数据
        $title = trim($input['title']);
        $author = trim($input['author']);
        $publish_year = intval($input['publish_year']);
        $price = floatval($input['price']);
        $category = trim($input['category']);
        
        if (empty($title) || empty($author) || empty($category)) {
            sendResponse(false, null, '书名、作者和分类不能为空', 400);
        }
        
        if ($publish_year < 1900 || $publish_year > 2030) {
            sendResponse(false, null, '出版年份必须在1900-2030之间', 400);
        }
        
        if ($price <= 0) {
            sendResponse(false, null, '价格必须大于0', 400);
        }
        
        // 检查是否已存在相同书名和作者的书
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE title = ? AND author = ?");
        $stmt->execute([$title, $author]);
        if ($stmt->fetchColumn() > 0) {
            sendResponse(false, null, '该书名和作者已存在', 400);
        }
        
        // 插入新图书
        $sql = "INSERT INTO books (title, author, publish_year, price, category) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $author, $publish_year, $price, $category]);
        
        $bookId = $pdo->lastInsertId();
        
        // 获取新添加的图书信息
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $newBook = $stmt->fetch();
        
        sendResponse(true, $newBook, '图书添加成功');
        
    } catch (PDOException $e) {
        sendResponse(false, null, '添加图书失败: ' . $e->getMessage(), 500);
    }
}

// 更新图书信息
if ($method === 'PUT' && $action === 'update_book') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'], $input['title'], $input['author'], $input['publish_year'], $input['price'], $input['category'])) {
            sendResponse(false, null, '缺少必要参数', 400);
        }
        
        $id = intval($input['id']);
        $title = trim($input['title']);
        $author = trim($input['author']);
        $publish_year = intval($input['publish_year']);
        $price = floatval($input['price']);
        $category = trim($input['category']);
        
        if (empty($title) || empty($author) || empty($category)) {
            sendResponse(false, null, '书名、作者和分类不能为空', 400);
        }
        
        if ($publish_year < 1900 || $publish_year > 2030) {
            sendResponse(false, null, '出版年份必须在1900-2030之间', 400);
        }
        
        if ($price <= 0) {
            sendResponse(false, null, '价格必须大于0', 400);
        }
        
        // 检查图书是否存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() === 0) {
            sendResponse(false, null, '图书不存在', 404);
        }
        
        // 更新图书信息
        $sql = "UPDATE books SET title = ?, author = ?, publish_year = ?, price = ?, category = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $author, $publish_year, $price, $category, $id]);
        
        // 获取更新后的图书信息
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $updatedBook = $stmt->fetch();
        
        sendResponse(true, $updatedBook, '图书信息更新成功');
        
    } catch (PDOException $e) {
        sendResponse(false, null, '更新图书失败: ' . $e->getMessage(), 500);
    }
}

// 删除图书
if ($method === 'DELETE' && $action === 'delete_book') {
    try {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            sendResponse(false, null, '缺少图书ID', 400);
        }
        
        $id = intval($id);
        
        // 检查图书是否存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() === 0) {
            sendResponse(false, null, '图书不存在', 404);
        }
        
        // 删除图书
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse(true, null, '图书删除成功');
        
    } catch (PDOException $e) {
        sendResponse(false, null, '删除图书失败: ' . $e->getMessage(), 500);
    }
}

// 默认响应
sendResponse(false, null, '无效的请求', 404);
?>
