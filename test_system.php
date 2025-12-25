<?php
/**
 * 图书管理系统测试文件
 * 用于测试系统各个功能是否正常工作
 */

echo "<h1>图书管理系统测试</h1>";

// 测试数据库连接
echo "<h2>1. 数据库连接测试</h2>";
try {
    require_once 'SQL Connection/db_connect.php';
    $pdo = getPDOConnection();
    echo "<p style='color: green;'>✓ 数据库连接成功</p>";
    
    // 测试表是否存在
    $tables = ['users', 'books', 'categories', 'tags', 'comments', 'chat_messages'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ 数据表 '$table' 存在</p>";
        } else {
            echo "<p style='color: red;'>✗ 数据表 '$table' 不存在</p>";
        }
    }
    
    // 测试数据
    echo "<h2>2. 数据测试</h2>";
    
    // 用户数据
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>用户数量: $userCount</p>";
    
    // 图书数据
    $bookCount = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    echo "<p>图书数量: $bookCount</p>";
    
    // 分类数据
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    echo "<p>分类数量: $categoryCount</p>";
    
    // 标签数据
    $tagCount = $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
    echo "<p>标签数量: $tagCount</p>";
    
    // 评论数据
    $commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    echo "<p>评论数量: $commentCount</p>";
    
    // 群聊消息数据
    $messageCount = $pdo->query("SELECT COUNT(*) FROM chat_messages")->fetchColumn();
    echo "<p>群聊消息数量: $messageCount</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 数据库连接失败: " . $e->getMessage() . "</p>";
}

// 测试文件存在性
echo "<h2>3. 文件存在性测试</h2>";
$files = [
    'index.php',
    'php/index.php',
    'php/login.php',
    'php/home.php',
    'php/user_manage.php',
    'php/book_manage.php',
    'php/category_manage.php',
    'php/tag_manage.php',
    'php/comment_manage.php',
    'php/group_manage.php',
    'php/site_setting.php',
    'php/logout.php',
    'php/api.php',
    'SQL Connection/db_connect.php',
    'css/user_manage.css',
    'js/script.js',
    'sql/books.sql'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ 文件 '$file' 存在</p>";
    } else {
        echo "<p style='color: red;'>✗ 文件 '$file' 不存在</p>";
    }
}

// 测试PHP语法
echo "<h2>4. PHP语法测试</h2>";
$phpFiles = [
    'php/index.php',
    'php/login.php',
    'php/home.php',
    'php/user_manage.php',
    'php/book_manage.php',
    'php/category_manage.php',
    'php/tag_manage.php',
    'php/comment_manage.php',
    'php/group_manage.php',
    'php/site_setting.php',
    'php/logout.php',
    'php/api.php'
];

foreach ($phpFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<p style='color: green;'>✓ $file 语法正确</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 语法错误: $output</p>";
    }
}

// 测试登录功能
echo "<h2>5. 登录功能测试</h2>";
echo "<p>测试账号: admin / admin123</p>";
echo "<p>测试账号: 123 / 123456</p>";
echo "<p><a href='php/login.php'>点击这里测试登录</a></p>";

// 测试API接口
echo "<h2>6. API接口测试</h2>";
echo "<p><a href='php/api.php?action=get_books'>测试获取图书API</a></p>";
echo "<p><a href='php/api.php?action=get_stats'>测试统计信息API</a></p>";

// 系统信息
echo "<h2>7. 系统信息</h2>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>服务器软件: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";

// 导航链接
echo "<h2>8. 系统导航</h2>";
echo "<ul>";
echo "<li><a href='php/index.php'>首页</a></li>";
echo "<li><a href='php/login.php'>登录页面</a></li>";
echo "<li><a href='php/home.php'>系统首页（需要登录）</a></li>";
echo "<li><a href='php/user_manage.php'>用户管理（需要管理员权限）</a></li>";
echo "<li><a href='php/book_manage.php'>图书管理</a></li>";
echo "<li><a href='php/category_manage.php'>分类管理</a></li>";
echo "<li><a href='php/tag_manage.php'>标签管理</a></li>";
echo "<li><a href='php/comment_manage.php'>评论管理</a></li>";
echo "<li><a href='php/group_manage.php'>群聊管理</a></li>";
echo "<li><a href='php/site_setting.php'>网站设置</a></li>";
echo "</ul>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { margin: 5px 0; }
</style>