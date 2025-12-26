<?php
// 创建数据库脚本
$host = '127.0.0.1';
$username = 'root';
$password = 'root';
$charset = 'utf8mb4';

try {
    // 连接到MySQL服务器（不指定数据库）
    $pdo = new PDO(
        "mysql:host=$host;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    echo "<h2>创建数据库 admin_book</h2>";

    // 检查数据库是否已存在
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'admin_book'");
    if ($stmt->fetch()) {
        echo "数据库 admin_book 已存在，将重新创建...<br>";
        $pdo->exec("DROP DATABASE IF EXISTS admin_book");
    }

    // 创建数据库
    $pdo->exec("CREATE DATABASE admin_book CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ 数据库 admin_book 创建成功！<br>";

    // 连接到新创建的数据库
    $pdo = new PDO(
        "mysql:host=$host;dbname=admin_book;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    // 读取并执行SQL文件
    $sqlFile = file_get_contents('sql/books.sql');
    if (!$sqlFile) {
        die("无法读取SQL文件！");
    }

    // 分割SQL语句
    $queries = explode(';', $sqlFile);
    $successCount = 0;
    $errorCount = 0;

    echo "<br>开始导入数据表...<br><br>";

    foreach ($queries as $query) {
        $query = trim($query);

        if (empty($query)) {
            continue;
        }

        if (strpos($query, '--') === 0) {
            continue;
        }

        try {
            $pdo->exec($query);
            $successCount++;
            if ($successCount <= 10) {
                echo "✓ 表创建成功<br>";
            }
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ 执行失败: " . $e->getMessage() . "<br>";
        }
    }

    echo "<br><h3>导入完成</h3>";
    echo "成功: {$successCount} 条<br>";
    echo "失败: {$errorCount} 条<br><br>";

    // 验证数据
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ users表记录数: " . $result['count'] . "<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $result = $stmt->fetch();
    echo "✓ books表记录数: " . $result['count'] . "<br>";

    echo "<br><h3>测试账号</h3>";
    $stmt = $pdo->query("SELECT username, password, role FROM users");
    $users = $stmt->fetchAll();

    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>用户名</th><th>密码</th><th>角色</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['password']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<br><strong>数据库初始化完成！现在可以使用以下账号登录：</strong><br>";
    echo "管理员：admin / admin123<br>";
    echo "员工：YJM / yjm123<br>";

} catch (PDOException $e) {
    die("错误：" . $e->getMessage());
}
?>
