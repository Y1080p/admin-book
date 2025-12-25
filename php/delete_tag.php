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

// 获取标签ID
$tagId = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;

if ($tagId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的标签ID']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 检查标签是否存在
    $checkSql = "SELECT id, name FROM tags WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$tagId]);
    $tag = $checkStmt->fetch();
    
    if (!$tag) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '标签不存在']);
        exit();
    }
    
    // 删除标签
    $deleteSql = "DELETE FROM tags WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$tagId]);
    
    if ($deleteStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '标签删除成功']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>