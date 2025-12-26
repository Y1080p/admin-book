<?php
// 数据库配置函数：获取PDO连接对象（适配Render环境变量，兼容Railway MySQL）
function getPDOConnection() {
    // 从Render环境变量中读取数据库配置（无需写死，灵活适配部署环境）
    $host = getenv('DB_HOST');       // 读取环境变量：Railway MySQL主机地址
    $dbname = getenv('DB_NAME');     // 读取环境变量：Railway默认数据库名
    $username = getenv('DB_USER');   // 读取环境变量：数据库用户名
    $password = getenv('DB_PASS');   // 读取环境变量：数据库密码
    $port = getenv('DB_PORT');       // 读取环境变量：Railway非默认端口（必须指定，否则连接失败）

    // 如果环境变量为空，使用本地开发环境配置
    if (empty($host) || empty($dbname) || empty($username) || empty($password)) {
        $host = '127.0.0.1';        // 本地MySQL主机
        $dbname = 'book_db';          // 本地数据库名
        $username = 'root';          // 本地数据库用户名
        $password = 'root';          // 本地数据库密码（根据phpstudy配置调整）
        $port = '3306';             // MySQL默认端口
    }

    $charset = 'utf8mb4';            // 升级为utf8mb4，支持emoji等特殊字符（比utf8更全面）

    try {
        // 建立PDO连接：关键添加 port=$port 参数，指定非默认端口
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // 抛出异常模式，便于调试错误
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // 默认关联数组返回，更易使用
                PDO::ATTR_AUTOCOMMIT => true,                         // 开启自动提交事务
                PDO::ATTR_EMULATE_PREPARES => false,                  // 关闭预处理语句模拟，提升性能和安全性
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"  // 初始化字符集，避免中文乱码
            ]
        );

        // 连接成功，返回PDO对象
        return $pdo;
    } catch (PDOException $e) {
        // 连接失败：记录错误信息，但不要 die()，让调用者处理
        error_log("数据库连接失败：" . $e->getMessage());
        throw new Exception("数据库连接失败：" . $e->getMessage());
    }
}
?>
