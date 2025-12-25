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

// 获取POST参数
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

// 验证参数
if ($category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查分类是否存在
    $checkSql = "SELECT id FROM categories WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$category_id]);
    
    if (!$checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类不存在']);
        exit();
    }
    
    // 检查是否有图书关联此分类
    $checkBooksSql = "SELECT id FROM books WHERE category_id = ? LIMIT 1";
    $checkBooksStmt = $pdo->prepare($checkBooksSql);
    $checkBooksStmt->execute([$category_id]);
    
    if ($checkBooksStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '该分类下有关联的图书，无法删除']);
        exit();
    }
    
    // 删除分类
    $deleteSql = "DELETE FROM categories WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute([$category_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '分类删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>