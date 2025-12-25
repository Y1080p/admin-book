<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 获取统计数据
$pdo = getPDOConnection();

// 用户数量
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 图书数量
$bookCount = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();

// 分类数量
$categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// 群聊数量
$chatGroupCount = $pdo->query("SELECT COUNT(*) FROM chat_groups")->fetchColumn();

// 最新图书
$latestBooks = $pdo->query("SELECT title, author, create_time FROM books ORDER BY create_time DESC LIMIT 5")->fetchAll();

// 最新评论
$latestComments = $pdo->query("SELECT c.content, u.username, b.title, c.create_time 
                                FROM comments c 
                                LEFT JOIN users u ON c.user_id = u.id 
                                LEFT JOIN books b ON c.book_id = b.id 
                                ORDER BY c.create_time DESC LIMIT 5")->fetchAll();

// 用户管理汇总数据
// 今日新增用户
$todayUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(create_time) = CURDATE()")->fetchColumn();

// 本周新增用户
$weekUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE YEARWEEK(create_time) = YEARWEEK(NOW())")->fetchColumn();

// 本月新增用户
$monthUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE YEAR(create_time) = YEAR(NOW()) AND MONTH(create_time) = MONTH(NOW())")->fetchColumn();

// 最新注册用户
$latestUsers = $pdo->query("SELECT username, email, create_time FROM users ORDER BY create_time DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>首页 - 图书管理系统</title>
    <link rel="stylesheet" href="/admin-book/css/user_manage.css">
    <style>
        .dashboard {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            text-align: center;
            transition: box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #409eff;
        }
        
        .recent-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .recent-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            z-index: 1;
        }
        
        .recent-card:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            transform: translateY(-4px);
            border-color: rgba(64, 158, 255, 0.5);
            z-index: 2;
        }
        
        .recent-card h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .recent-list {
            list-style: none;
        }
        
        .recent-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .recent-list li:last-child {
            border-bottom: none;
        }
        
        .book-title {
            font-weight: 500;
            color: #333;
        }
        
        .book-info {
            font-size: 12px;
            color: #666;
        }
        
        .comment-content {
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .comment-info {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- 左侧导航栏 -->
    <div class="sidebar">
        <div class="logo">图书管理后台管理</div>
        <ul>
            <li><a href="home.php" class="active"><i class="icon fa fa-home"></i>首页</a></li>
            <li><a href="site_setting.php"><i class="icon fa fa-cog"></i>网站设置</a></li>
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
        <div class="random-joke">欢迎使用图书管理系统</div>
        <div class="user-info">欢迎你，<?php echo $_SESSION['username']; ?>！<a href="logout.php">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="dashboard">
            <h1>系统概览</h1>
            
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>用户总数</h3>
                    <div class="stat-number"><?php echo $userCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>图书总数</h3>
                    <div class="stat-number"><?php echo $bookCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>分类数量</h3>
                    <div class="stat-number"><?php echo $categoryCount; ?></div>
                </div>
                <div class="stat-card">
                    <h3>群聊总数</h3>
                    <div class="stat-number"><?php echo $chatGroupCount; ?></div>
                </div>
            </div>
            
            <!-- 用户管理汇总 -->
            <div class="recent-section">
                <div class="recent-card">
                    <h3>用户增长统计</h3>
                    <ul class="recent-list">
                        <li>
                            <div class="book-title">今日新增用户</div>
                            <div class="book-info">
                                数量：<span style="color: #409eff; font-weight: bold;"><?php echo $todayUsers; ?></span> 人
                            </div>
                        </li>
                        <li>
                            <div class="book-title">本周新增用户</div>
                            <div class="book-info">
                                数量：<span style="color: #409eff; font-weight: bold;"><?php echo $weekUsers; ?></span> 人
                            </div>
                        </li>
                        <li>
                            <div class="book-title">本月新增用户</div>
                            <div class="book-info">
                                数量：<span style="color: #409eff; font-weight: bold;"><?php echo $monthUsers; ?></span> 人
                            </div>
                        </li>
                        <li>
                            <div class="book-title">累计用户总数</div>
                            <div class="book-info">
                                数量：<span style="color: #409eff; font-weight: bold;"><?php echo $userCount; ?></span> 人
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="recent-card">
                    <h3>最新注册用户</h3>
                    <ul class="recent-list">
                        <?php foreach ($latestUsers as $user): ?>
                        <li>
                            <div class="book-title"><?php echo $user['username']; ?></div>
                            <div class="book-info">
                                邮箱：<?php echo $user['email']; ?> | 
                                注册时间：<?php echo date('m-d H:i', strtotime($user['create_time'])); ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>