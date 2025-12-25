<?php
// 测试数据库连接和操作
require_once 'SQL Connection/db_connect.php';

try {
    $pdo = getPDOConnection();
    
    echo "<h2>数据库连接测试</h2>";
    echo "数据库连接成功！<br><br>";
    
    // 测试查询
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "当前用户表记录数: " . $result['count'] . "<br>";
    
    // 显示所有用户
    $stmt = $pdo->query("SELECT id, username, role FROM users");
    echo "<h3>当前用户列表：</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>用户名</th><th>角色</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 测试插入操作
    echo "<h3>测试插入操作：</h3>";
    $testUsername = 'test_user_' . time();
    $testPassword = 'test123';
    $testRole = '员工';
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 1)");
    $result = $stmt->execute([$testUsername, $testPassword, $testRole]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo "插入测试用户成功！<br>";
        echo "新用户ID: " . $pdo->lastInsertId() . "<br>";
        
        // 验证插入
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "插入后用户表记录数: " . $result['count'] . "<br>";
        
        // 删除测试用户
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$testUsername]);
        echo "删除测试用户成功！<br>";
        
    } else {
        echo "插入测试用户失败！<br>";
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "<br>";
}

?>

<h3>测试说明：</h3>
<ul>
<li>如果数据库连接成功，会显示当前用户列表</li>
<li>会尝试插入一个测试用户，然后删除它</li>
<li>如果所有操作都成功，说明数据库连接和操作正常</li>
<li>如果失败，会显示具体的错误信息</li>
</ul>