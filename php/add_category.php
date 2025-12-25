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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// 验证参数
if (empty($name)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
    exit();
}

if ($sort_order < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '排序值不能为负数']);
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
    
    // 检查分类名称是否已存在
    $checkSql = "SELECT id FROM categories WHERE name = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$name]);
    
    if ($checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类名称已存在']);
        exit();
    }
    
    // 插入新分类
    $insertSql = "INSERT INTO categories (name, description, sort_order, status, create_time) VALUES (?, ?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $result = $insertStmt->execute([$name, $description, $sort_order, $status]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '分类添加成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '添加失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>