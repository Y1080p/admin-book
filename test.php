<?php
// 测试文件 - 验证系统配置
header('Content-Type: text/html; charset=utf-8');

echo "<h1>图书管理系统测试页面</h1>";

// 测试数据库连接
echo "<h2>1. 数据库连接测试</h2>";
try {
    require_once 'SQL Connection/db_connect.php';
    $pdo = getPDOConnection();
    echo "<p style='color: green;'>✓ 数据库连接成功</p>";
    
    // 测试查询
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $result = $stmt->fetch();
    echo "<p>数据库中有 " . $result['count'] . " 本图书</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 数据库连接失败: " . $e->getMessage() . "</p>";
}

// 测试文件权限
echo "<h2>2. 文件权限测试</h2>";
$files = [
    'index.php',
    'php/api.php',
    'css/style.css',
    'js/script.js',
    'SQL Connection/db_connect.php',
    'css/user_manage.css',
    'php/user_manage.php',
    'sql/books.sql',
    '.htaccess',
    'test.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<p style='color: green;'>✓ 文件可读: $file</p>";
        } else {
            echo "<p style='color: red;'>✗ 文件不可读: $file</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ 文件不存在: $file</p>";
    }
}

// 测试PHP配置
echo "<h2>3. PHP配置检查</h2>";
echo "<p>PHP版本: " . PHP_VERSION . "</p>";
echo "<p>PDO MySQL支持: " . (extension_loaded('pdo_mysql') ? '✓ 已启用' : '✗ 未启用') . "</p>";
echo "<p>JSON支持: " . (extension_loaded('json') ? '✓ 已启用' : '✗ 未启用') . "</p>";

// 测试API端点
echo "<h2>4. API端点测试</h2>";
$apiTests = [
    'get_books' => '获取图书列表',
    'get_stats' => '获取统计信息',
    'get_categories' => '获取分类列表'
];

foreach ($apiTests as $action => $description) {
    $url = "php/api.php?action=$action";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "<p style='color: green;'>✓ $description: 成功</p>";
        } else {
            echo "<p style='color: red;'>✗ $description: 响应格式错误</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ $description: 请求失败</p>";
    }
}

// 测试完成
echo "<h2>测试完成</h2>";
echo "<p><a href='index.php'>点击这里访问图书管理系统</a></p>";
echo "<p><a href='php/user_manage.php'>点击这里访问用户管理系统</a></p>";
?>