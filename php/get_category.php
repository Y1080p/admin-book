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

// 获取GET参数
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 验证参数
if ($category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 查询分类信息
    $sql = "SELECT * FROM categories WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if ($category) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'category' => $category]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类不存在']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>