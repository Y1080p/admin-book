<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 诊断信息 ===\n\n";

echo "1. PHP 版本: " . phpversion() . "\n";
echo "2. 服务器信息: " . $_SERVER['SERVER_SOFTWARE'] ?? '未知' . "\n";
echo "3. 文档根目录: " . $_SERVER['DOCUMENT_ROOT'] ?? '未知' . "\n";
echo "4. 当前脚本路径: " . __FILE__ . "\n";
echo "5. 请求 URI: " . ($_SERVER['REQUEST_URI'] ?? '未知') . "\n";
echo "6. 请求方法: " . ($_SERVER['REQUEST_METHOD'] ?? '未知') . "\n";
echo "7. HTTPS: " . (isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : 'off') . "\n";
echo "8. 端口: " . ($_SERVER['SERVER_PORT'] ?? '未知') . "\n";

echo "\n=== 文件检查 ===\n";

$files = [
    'api.php',
    'index.php',
    'SQL Connection/db_connect.php',
    'php/api.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    $readable = $exists && is_readable($file);
    echo "- $file: " . ($exists ? '存在' : '不存在') . ", " . ($readable ? '可读' : '不可读') . "\n";
}

echo "\n=== 数据库连接测试 ===\n";
if (file_exists('SQL Connection/db_connect.php')) {
    require_once 'SQL Connection/db_connect.php';
    try {
        $pdo = getPDOConnection();
        echo "数据库连接成功！\n";
        echo "数据库主机: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
    } catch (Exception $e) {
        echo "数据库连接失败: " . $e->getMessage() . "\n";
    }
} else {
    echo "db_connect.php 文件不存在！\n";
}

echo "\n=== Session 测试 ===\n";
session_start();
echo "Session ID: " . session_id() . "\n";

echo "\n=== 环境变量 ===\n";
$env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT', 'RENDER'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo "$var: " . ($value !== false ? ($var === 'DB_PASS' ? '***隐藏***' : $value) : '未设置') . "\n";
}

echo "\n=== 诊断完成 ===";
?>
