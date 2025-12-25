<?php
// 数据库安装脚本
require_once 'SQL Connection/db_connect.php';

try {
    $pdo = getPDOConnection();
    
    echo "<h2>数据库安装脚本</h2>";
    
    // 读取SQL文件
    $sqlFile = file_get_contents('sql/books.sql');
    
    if (!$sqlFile) {
        die("无法读取SQL文件！");
    }
    
    // 分割SQL语句
    $queries = explode(';', $sqlFile);
    $successCount = 0;
    $errorCount = 0;
    
    echo "开始执行SQL语句...<br><br>";
    
    foreach ($queries as $query) {
        // 去除空白字符
        $query = trim($query);
        
        // 跳过空查询
        if (empty($query)) {
            continue;
        }
        
        // 跳过注释
        if (strpos($query, '--') === 0) {
            continue;
        }
        
        try {
            // 执行SQL语句
            $pdo->exec($query);
            $successCount++;
            echo "✓ 执行成功: " . substr($query, 0, 50) . "...<br>";
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ 执行失败: " . $e->getMessage() . "<br>";
            echo "&nbsp;&nbsp;SQL: " . substr($query, 0, 100) . "...<br>";
        }
    }
    
    echo "<br><h3>执行结果</h3>";
    echo "成功: {$successCount} 条<br>";
    echo "失败: {$errorCount} 条<br><br>";
    
    // 验证安装结果
    echo "<h3>验证安装结果</h3>";
    
    // 检查users表
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "users表记录数: " . $result['count'] . "<br>";
        
        // 显示测试用户
        $stmt = $pdo->query("SELECT username, password, role FROM users");
        $users = $stmt->fetchAll();
        
        echo "<br><strong>测试用户账号：</strong><br>";
        foreach ($users as $user) {
            echo "用户名: {$user['username']}, 密码: {$user['password']}, 角色: {$user['role']}<br>";
        }
        
    } catch (PDOException $e) {
        echo "验证失败: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><strong>安装完成！</strong><br>";
    echo "请使用以下账号登录：<br>";
    echo "管理员：admin / admin123<br>";
    echo "普通用户：123 / 123456<br>";
    
} catch (PDOException $e) {
    echo "数据库连接错误：" . $e->getMessage() . "<br>";
}
?>