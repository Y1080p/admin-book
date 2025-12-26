<?php
// 第一步：添加CORS跨域头（放在最开头）
header("Access-Control-Allow-Origin: https://stunning-biscochitos-49d12b.netlify.app");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
// 处理OPTIONS预请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 获取图书ID
$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的图书ID']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 查询图书信息
    $sql = "SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    
    if ($book) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'book' => $book]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '图书不存在']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>
