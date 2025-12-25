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
$category_ids_json = isset($_POST['category_ids']) ? $_POST['category_ids'] : '[]';
$category_ids = json_decode($category_ids_json, true);

// 验证参数
if (!is_array($category_ids) || empty($category_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请选择要删除的分类']);
    exit();
}

// 验证分类ID格式
$valid_ids = [];
foreach ($category_ids as $id) {
    $id = intval($id);
    if ($id > 0) {
        $valid_ids[] = $id;
    }
}

if (empty($valid_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '分类ID无效']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查分类是否存在
    $placeholders = str_repeat('?,', count($valid_ids) - 1) . '?';
    $checkSql = "SELECT id FROM categories WHERE id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($valid_ids);
    $existing_ids = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($existing_ids) != count($valid_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分分类不存在']);
        exit();
    }
    
    // 检查是否有图书关联这些分类
    $checkBooksSql = "SELECT DISTINCT category_id FROM books WHERE category_id IN ($placeholders) LIMIT 1";
    $checkBooksStmt = $pdo->prepare($checkBooksSql);
    $checkBooksStmt->execute($valid_ids);
    
    if ($checkBooksStmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分分类下有关联的图书，无法删除']);
        exit();
    }
    
    // 开始事务
    $pdo->beginTransaction();
    
    try {
        // 批量删除分类
        $deleteSql = "DELETE FROM categories WHERE id IN ($placeholders)";
        $deleteStmt = $pdo->prepare($deleteSql);
        $result = $deleteStmt->execute($valid_ids);
        
        if ($result) {
            $pdo->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
        } else {
            $pdo->rollBack();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '批量删除失败']);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除过程中发生错误: ' . $e->getMessage()]);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
}
?>