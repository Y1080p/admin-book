<?php
// 全局配置文件
// 定义项目基础路径（适配不同部署环境）

// 检测是否在 Render 等线上环境
if (getenv('RENDER') || strpos($_SERVER['SERVER_NAME'] ?? '', 'render.com') !== false) {
    // 线上环境：Render 根目录是 /
    define('BASE_URL', '/');
    define('CSS_PATH', '/css/');
    define('JS_PATH', '/js/');
    define('IMAGE_PATH', '/images/');
    define('PHP_PATH', '/php/');
} else {
    // 本地开发环境：根据实际目录结构配置
    define('BASE_URL', '/admin-book/');
    define('CSS_PATH', '/css/');
    define('JS_PATH', '/js/');
    define('IMAGE_PATH', '/images/');
    define('PHP_PATH', '/php/');
}

// 统一重定向路径函数（避免相对路径问题）
function redirect($page) {
    // 如果页面已经在 php/ 目录下，直接跳转
    $path = PHP_PATH . $page;
    header('Location: ' . $path);
    exit();
}
?>
