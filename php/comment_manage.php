<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 查询评论数据
$pdo = getPDOConnection();
$sql = "SELECT c.*, b.title as book_title, u.username as user_name 
        FROM comments c 
        LEFT JOIN books b ON c.book_id = b.id 
        LEFT JOIN users u ON c.user_id = u.id";
$stmt = $pdo->query($sql);
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>评论管理 - 后台管理</title>
    <link rel="stylesheet" href="/admin-book/css/user_manage.css">
    <style>
        /* 状态切换开关样式 */
        .status-switch {
            width: 40px;
            height: 20px;
            background-color: #ccc;
            border-radius: 10px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
            border: none;
            outline: none;
            padding: 0;
            margin: 0;
        }
        
        .status-switch.on {
            background-color: #67c23a;
        }
        
        .status-switch::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background-color: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }
        
        .status-switch.on::before {
            transform: translateX(20px);
        }
    </style>
</head>
<body>
    <!-- 左侧导航栏 -->
    <div class="sidebar">
        <div class="logo">图书管理后台管理</div>
        <ul>
            <li><a href="index.php"><i class="icon fa fa-home"></i>首页</a></li>
            <li><a href="site_setting.php"><i class="icon fa fa-cog"></i>网站设置</a></li>
            <li><a href="user_manage.php"><i class="icon fa fa-user"></i>用户管理</a></li>
            <!-- 新增图书管理相关导航 -->
            <li><a href="book_manage.php"><i class="icon fa fa-book"></i>图书管理</a></li>
            <li><a href="category_manage.php"><i class="icon fa fa-list"></i>分类管理</a></li>
            <li><a href="tag_manage.php"><i class="icon fa fa-tags"></i>标签管理</a></li>
            <!-- <li><a href="comment_manage.php" class="active"><i class="icon fa fa-comment"></i>评论管理</a></li> -->
            <li><a href="group_manage.php"><i class="icon fa fa-comments"></i>群聊管理</a></li>
        </ul>
    </div>

    <!-- 顶部栏 -->
    <div class="topbar">
        <div class="random-joke">评论管理</div>
        <div class="user-info">欢迎你！<a href="#">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="search-bar">
            <select name="status">
                <option value="">全部状态</option>
                <option value="1">显示</option>
                <option value="0">隐藏</option>
            </select>
            <input type="text" placeholder="图书标题">
            <input type="text" placeholder="用户名">
            <button>搜索</button>
            <button class="reset-btn">重置</button>
        </div>

        <div class="btn-group">
            <button class="edit-btn">修改</button>
            <button class="del-btn">删除</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>ID</th>
                    <th>图书标题</th>
                    <th>用户名</th>
                    <th>评论内容</th>
                    <th>评分</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment) : ?>
                <tr>
                    <td><input type="checkbox"></td>
                    <td><?php echo $comment['id']; ?></td>
                    <td><?php echo isset($comment['book_title']) ? $comment['book_title'] : ''; ?></td>
                    <td><?php echo isset($comment['user_name']) ? $comment['user_name'] : ''; ?></td>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?php echo $comment['content']; ?></td>
                    <td>
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $comment['rating'] ? '★' : '☆';
                        }
                        ?>
                    </td>
                    <td>
                        <button class="status-switch <?php echo $comment['status'] ? 'on' : ''; ?>"></button>
                    </td>
                    <td><?php echo $comment['create_time']; ?></td>
                    <td>
                        <a href="#">修改</a>
                        <a href="#">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            共 <?php echo count($comments); ?> 条 
            <select>
                <option value="10">10条/页</option>
                <option value="20">20条/页</option>
                <option value="50">50条/页</option>
            </select>
            <a href="#">&lt;</a>
            <a href="#" class="current">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#">&gt;</a>
            前往 <input type="text" value="1" size="1"> 页
        </div>
    </div>
</body>
</html>