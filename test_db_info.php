<?php
require_once 'SQL Connection/db_connect.php';

try {
    $pdo = getPDOConnection();
    
    // 获取当前数据库名
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $dbName = $stmt->fetch()['db_name'];
    
    // 获取表列表
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 获取books表的数据量
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $bookCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'database' => $dbName,
        'tables' => $tables,
        'books_count' => $bookCount,
        'message' => '数据库连接成功'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => '数据库连接失败'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
