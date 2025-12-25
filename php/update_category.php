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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// 验证参数
if ($category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

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
    
    // 检查分类是否存在
    $checkSql = "SELECT id FROM categories WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$category_id]);
    
    if (!$checkStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类不存在']);
        exit();
    }
    
    // 检查分类名称是否已存在（排除当前分类）
    $checkNameSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
    $checkNameStmt = $pdo->prepare($checkNameSql);
    $checkNameStmt->execute([$name, $category_id]);
    
    if ($checkNameStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '分类名称已存在']);
        exit();
    }
    
    // 更新分类信息
    $updateSql = "UPDATE categories SET name = ?, description = ?, sort_order = ?, status = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([$name, $description, $sort_order, $status, $category_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '分类修改成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '修改失败']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>