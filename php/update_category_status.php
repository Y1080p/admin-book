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
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// 验证参数
if ($category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

if (!in_array($status, [0, 1])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '状态值无效']);
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
    
    // 更新分类状态
    $updateSql = "UPDATE categories SET status = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([$status, $category_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'newStatus' => $status]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '更新失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>