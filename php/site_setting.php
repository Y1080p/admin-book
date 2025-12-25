<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 设置默认值
$site_name = '图书管理系统';
$site_description = '专业的图书管理平台';
$site_keywords = '图书,管理,系统';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>网站设置 - 后台管理</title>
    <link rel="stylesheet" href="/admin-book/css/user_manage.css">
</head>
<body>
    <!-- 左侧导航栏 -->
    <div class="sidebar">
        <div class="logo">图书管理后台管理</div>
        <ul>
            <li><a href="home.php"><i class="icon fa fa-home"></i>首页</a></li>
            <li><a href="site_setting.php" class="active"><i class="icon fa fa-cog"></i>网站设置</a></li>
            <li><a href="user_manage.php"><i class="icon fa fa-user"></i>用户管理</a></li>
            <!-- 新增图书管理相关导航 -->
            <li><a href="book_manage.php"><i class="icon fa fa-book"></i>图书管理</a></li>
            <li><a href="category_manage.php"><i class="icon fa fa-list"></i>分类管理</a></li>
            <li><a href="tag_manage.php"><i class="icon fa fa-tags"></i>标签管理</a></li>
            <!-- <li><a href="comment_manage.php"><i class="icon fa fa-comment"></i>评论管理</a></li> -->
            <li><a href="group_manage.php"><i class="icon fa fa-comments"></i>群聊管理</a></li>
        </ul>
    </div>

    <!-- 顶部栏 -->
    <div class="topbar">
        <div class="random-joke">网站设置</div>
        <div class="user-info">欢迎你，<?php echo $_SESSION['username']; ?>！<a href="logout.php">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="setting-section">
            <h2>网站基本信息</h2>
            <div class="form-group">
                <label>网站名称：</label>
                <div class="setting-value"><?php echo htmlspecialchars($site_name); ?></div>
            </div>
            <div class="form-group">
                <label>网站描述：</label>
                <div class="setting-value"><?php echo htmlspecialchars($site_description); ?></div>
            </div>
            <div class="form-group">
                <label>网站关键词：</label>
                <div class="setting-value"><?php echo htmlspecialchars($site_keywords); ?></div>
            </div>
        </div>

        <div class="setting-section">
            <h2>系统信息</h2>
            <div class="system-info">
                <p><strong>PHP版本：</strong><?php echo phpversion(); ?></p>
                <p><strong>服务器软件：</strong><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '未知'; ?></p>
                <p><strong>数据库连接：</strong>正常</p>
                <p><strong>当前时间：</strong><?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>

    <style>
        .setting-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transition: box-shadow 0.3s ease;
        }
        
        .setting-section:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        
        .setting-section h2 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        
        .save-btn {
            background: #409eff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .save-btn:hover {
            background: #66b1ff;
        }
        
        .system-info p {
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .system-info strong {
            color: #333;
            min-width: 120px;
            display: inline-block;
        }
    </style>
</body>
</html>