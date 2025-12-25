<?php
// 检查数据库连接和表结构
require_once 'SQL Connection/db_connect.php';

try {
    $pdo = getPDOConnection();
    
    echo "<h2>数据库连接测试</h2>";
    echo "连接成功！<br><br>";
    
    // 检查users表结构
    echo "<h3>users表结构</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "users表不存在！<br>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>字段名</th><th>类型</th><th>是否为空</th><th>键</th><th>默认值</th><th>额外</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 检查users表中的数据
    echo "<h3>users表数据</h3>";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "users表为空！<br>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>用户名</th><th>密码</th><th>角色</th><th>状态</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['password']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['status']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 检查所有表
    echo "<h3>数据库中的所有表</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "数据库为空！<br>";
    } else {
        echo "数据库中的表：<br>";
        foreach ($tables as $table) {
            echo "- {$table}<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "数据库连接错误：" . $e->getMessage() . "<br>";
}
?>