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

// 获取图书ID
$bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

if ($bookId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的图书ID']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查图书是否存在
    $checkSql = "SELECT id, title FROM books WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$bookId]);
    $book = $checkStmt->fetch();
    
    if (!$book) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '图书不存在']);
        exit();
    }
    
    // 删除图书
    $deleteSql = "DELETE FROM books WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$bookId]);
    
    if ($deleteStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '图书删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>