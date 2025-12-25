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

// 获取标签ID数组
$tagIdsJson = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : '[]';
$tagIds = json_decode($tagIdsJson, true);

// 验证数据
if (!is_array($tagIds) || empty($tagIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请选择要删除的标签']);
    exit();
}

// 过滤有效的标签ID
$validTagIds = array_filter($tagIds, function($id) {
    return is_numeric($id) && $id > 0;
});

if (empty($validTagIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的标签ID']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查标签是否存在
    $placeholders = str_repeat('?,', count($validTagIds) - 1) . '?';
    $checkSql = "SELECT id, name FROM tags WHERE id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($validTagIds);
    $existingTags = $checkStmt->fetchAll();
    
    if (count($existingTags) !== count($validTagIds)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分标签不存在']);
        exit();
    }
    
    // 批量删除标签
    $deleteSql = "DELETE FROM tags WHERE id IN ($placeholders)";
    $deleteStmt = $pdo->prepare($deleteSql);
    $result = $deleteStmt->execute($validTagIds);
    
    if ($result && $deleteStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '批量删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>