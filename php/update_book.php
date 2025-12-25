<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 获取表单数据
$bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$author = isset($_POST['author']) ? trim($_POST['author']) : '';
$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
$publisher = isset($_POST['publisher']) ? trim($_POST['publisher']) : '';
$categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

// 验证数据
if ($bookId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的图书ID']);
    exit();
}

if (empty($title) || empty($author) || $categoryId <= 0 || $price <= 0 || $stock < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请填写完整的图书信息']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查图书是否存在
    $checkSql = "SELECT id FROM books WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$bookId]);
    
    if (!$checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '图书不存在']);
        exit();
    }
    
    // 更新图书信息
    $updateSql = "UPDATE books SET title = ?, author = ?, isbn = ?, publisher = ?, category_id = ?, status = ?, price = ?, stock = ?, update_time = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$title, $author, $isbn, $publisher, $categoryId, $status, $price, $stock, $bookId]);
    
    if ($updateStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '图书修改成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '图书信息未发生变化']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>