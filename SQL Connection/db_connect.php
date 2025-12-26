<?php
// 数据库配置函数：获取PDO连接对象
function getPDOConnection() {
    // 数据库配置（适配Railway MySQL公网连接）
    $host = 'yamabiko.proxy.rlwy.net';       // Railway MySQL主机地址
    $dbname = 'railway';                     // Railway默认数据库名
    $username = 'root';                      // 数据库用户名
    $password = 'uHBiTFwnSNhpYwfbtGxCLwTMmeRljpri'; // 数据库密码
    $port = '51207';                         // Railway非默认端口（必须指定，否则连接失败）
    $charset = 'utf8mb4';                    // 升级为utf8mb4，支持emoji等特殊字符（比utf8更全面）

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
        // 连接失败：记录错误信息（便于排查），并终止程序
        error_log("数据库连接失败：" . $e->getMessage()); // 写入错误日志
        die("数据库连接异常，请稍后重试！"); // 给用户友好提示，不暴露敏感信息
    }
}
?>
