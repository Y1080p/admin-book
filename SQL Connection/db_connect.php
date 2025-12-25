<?php
// 数据库配置函数
function getPDOConnection() {
    // 数据库配置（根据你的实际情况修改）
    $host = 'localhost';
    $dbname = 'book_db';
    $username = 'root';  // 你的数据库用户名
    $password = 'root';  // 你的数据库密码

    try {
        // 建立PDO连接
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_AUTOCOMMIT => true  // 确保自动提交开启
            ]
        );
        // 如果连接成功，返回PDO对象
        return $pdo;
    } catch (PDOException $e) {
        // 连接失败时终止程序并提示错误
        die("数据库连接失败：" . $e->getMessage());
    }
}