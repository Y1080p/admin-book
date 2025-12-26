<?php
// 简单的首页 - 检查 API 是否正常
error_reporting(E_ALL);
ini_set('display_errors', 0);

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Book API</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Admin Book API</h1>';

// 测试数据库连接
$dbConnected = false;
$dbError = '';
try {
    require_once 'SQL Connection/db_connect.php';
    $pdo = getPDOConnection();
    $dbConnected = true;
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

echo '<div class="status ' . ($dbConnected ? 'success' : 'error') . '">';
echo '<strong>数据库连接：</strong>' . ($dbConnected ? '成功 ✓' : '失败 ✗');
if (!$dbConnected) {
    echo '<br>错误信息：' . htmlspecialchars($dbError);
}
echo '</div>';

// 测试 API 端点
$apiEndpoints = [
    '/api.php?test' => '测试 API',
    '/api.php?health' => '健康检查',
    '/api.php?auth/check' => '检查登录状态',
];

echo '<h2>API 端点</h2>';
echo '<ul>';
foreach ($apiEndpoints as $endpoint => $description) {
    $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $endpoint;
    echo '<li><a href="' . $url . '">' . $description . '</a></li>';
}
echo '</ul>';

// 环境信息
echo '<h2>环境信息</h2>';
echo '<pre>';
echo 'PHP 版本: ' . phpversion() . "\n";
echo '服务器: ' . ($_SERVER['SERVER_SOFTWARE'] ?? '未知') . "\n";
echo '文档根目录: ' . ($_SERVER['DOCUMENT_ROOT'] ?? '未知') . "\n";
echo '请求 URI: ' . ($_SERVER['REQUEST_URI'] ?? '未知') . "\n";
echo '</pre>';

// 环境变量（隐藏敏感信息）
echo '<h2>环境变量</h2>';
echo '<pre>';
$envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PORT', 'RENDER'];
foreach ($envVars as $var) {
    $value = getenv($var);
    $display = ($value !== false) ? ($var === 'DB_PASS' ? '***隐藏***' : $value) : '未设置';
    echo $var . ': ' . $display . "\n";
}
echo '</pre>';

echo '<div class="status info">
    <strong>提示：</strong>
    <ul>
        <li>API 端点：/api.php?test（测试）、/api.php?auth/check（检查登录）、/api.php?auth/login（登录）</li>
        <li>请先在 Render 中配置数据库环境变量（DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT）</li>
        <li>登录页：<a href="php/login.php">php/login.php</a></li>
    </ul>
</div>';

echo '</body></html>';
?>