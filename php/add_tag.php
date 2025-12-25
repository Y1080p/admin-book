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

// 设置响应头为JSON
header('Content-Type: application/json');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
    exit();
}

// 获取并验证表单数据
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$color = isset($_POST['color']) ? trim($_POST['color']) : '#409eff';
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// 验证必填字段
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => '标签名称不能为空']);
    exit();
}

if (empty($color)) {
    echo json_encode(['success' => false, 'message' => '标签颜色不能为空']);
    exit();
}

try {
    // 连接数据库
    $pdo = getPDOConnection();
    
    // 检查标签是否已存在
    $checkSql = "SELECT id FROM tags WHERE name = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$name]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => '该标签已存在']);
        exit();
    }
    
    // 插入新标签数据
    $insertSql = "INSERT INTO tags (name, color, status, create_time) 
                  VALUES (?, ?, ?, NOW())";
    
    $insertStmt = $pdo->prepare($insertSql);
    $result = $insertStmt->execute([
        $name, 
        $color, 
        $status
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '标签添加成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '添加失败，请重试']);
    }
    
} catch (PDOException $e) {
    // 记录错误日志
    error_log('添加标签失败: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '数据库操作失败']);
}
?>