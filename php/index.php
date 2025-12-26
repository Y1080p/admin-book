<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 如果已经登录，重定向到首页
header('Location: home.php');
exit();

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 查询用户数据
$pdo = getPDOConnection();
$sql = "SELECT id, username, phone, email, status, avatar, gender, intro, role FROM users";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>用户管理 - 后台管理</title>
    <link rel="stylesheet" href="/css/user_manage.css">
</head>
<body>
    <!-- 左侧导航栏 -->
    <div class="sidebar">
        <div class="logo">图书管理后台管理</div>
        <ul>
            <li><a href="home.php"><i class="icon fa fa-home"></i>首页</a></li>
            <li><a href="site_setting.php"><i class="icon fa fa-cog"></i>网站设置</a></li>
            <li><a href="user_manage.php" class="active"><i class="icon fa fa-user"></i>用户管理</a></li>
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
        <div class="search-bar">
            <select name="role">
                <option value="">角色</option>
                <option value="admin">管理员</option>
                <option value="user">员工</option>
            </select>
            <select name="status">
                <option value="">状态</option>
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
            <input type="text" placeholder="用户账号">
            <input type="text" placeholder="手机号">
            <button>搜索</button>
            <button class="reset-btn">重置</button>
        </div>

        <div class="btn-group">
            <button class="add-btn">+ 新增</button>
            <button class="edit-btn">修改</button>
            <button class="del-btn">删除</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>ID</th>
                    <th>用户账号</th>
                    <th>手机号</th>
                    <th>邮箱</th>
                    <th>用户状态</th>
                    <th>头像</th>
                    <th>性别</th>
                    <th>简介</th>
                    <th>角色</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                <tr>
                    <td><input type="checkbox"></td>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo isset($user['phone']) ? $user['phone'] : ''; ?></td>
                    <td><?php echo isset($user['email']) ? $user['email'] : ''; ?></td>
                    <td>
                        <div class="status-switch <?php echo $user['status'] ? 'on' : ''; ?>"></div>
                    </td>
                    <td><img src="<?php echo isset($user['avatar']) ? $user['avatar'] : '/images/default_avatar.jpg'; ?>" alt="头像"></td>
                    <td><?php echo isset($user['gender']) ? $user['gender'] : '未知'; ?></td>
                    <td><?php echo isset($user['intro']) ? $user['intro'] : ''; ?></td>
                    <td><?php echo isset($user['role']) ? $user['role'] : '员工'; ?></td>
                    <td>
                        <a href="#">修改</a>
                        <a href="#">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            共 <?php echo count($users); ?> 条 
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