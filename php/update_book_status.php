<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit();
}

// 检查权限（只有管理员可以修改图书状态）
if ($_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无权限操作']);
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 获取POST参数
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// 验证参数
if ($book_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的图书ID']);
    exit();
}

if ($status !== 0 && $status !== 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '无效的状态值']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 更新图书状态
    $sql = "UPDATE books SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$status, $book_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'newStatus' => $status]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '更新失败，图书可能不存在']);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '数据库错误：' . $e->getMessage()]);
}
?>