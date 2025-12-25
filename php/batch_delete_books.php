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

// 获取图书ID数组
$bookIdsJson = isset($_POST['book_ids']) ? $_POST['book_ids'] : '';

if (empty($bookIdsJson)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '请选择要删除的图书']);
    exit();
}

$bookIds = json_decode($bookIdsJson, true);

if (!is_array($bookIds) || empty($bookIds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的图书ID数据']);
    exit();
}

try {
    $pdo = getPDOConnection();
    
    // 验证图书是否存在
    $placeholders = str_repeat('?,', count($bookIds) - 1) . '?';
    $checkSql = "SELECT id, title FROM books WHERE id IN ($placeholders)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($bookIds);
    $existingBooks = $checkStmt->fetchAll();
    
    if (count($existingBooks) !== count($bookIds)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '部分图书不存在']);
        exit();
    }
    
    // 开始事务
    $pdo->beginTransaction();
    
    // 批量删除图书
    $deleteSql = "DELETE FROM books WHERE id IN ($placeholders)";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute($bookIds);
    
    $deletedCount = $deleteStmt->rowCount();
    
    // 提交事务
    $pdo->commit();
    
    if ($deletedCount > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '批量删除成功', 'deleted_count' => $deletedCount]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '删除失败，请重试']);
    }
    
} catch (PDOException $e) {
    // 回滚事务
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>