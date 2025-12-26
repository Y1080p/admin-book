<?php
// 1. 优先处理OPTIONS预请求（跨域必加，避免浏览器拦截）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置信任的跨域域名（本地开发+线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 替换成你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回CORS头（安全，避免任意域名访问）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    // 用户管理接口涉及增删改查，允许所有常用请求方法
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 检测是否为API请求
$isApiRequest = in_array($origin, $allowedOrigins);

if ($isApiRequest) {
    header('Content-Type: application/json; charset=utf-8');
}

// 5. 启动session
session_start();

// 6. 登录检查
if (!isset($_SESSION['user_id'])) {
    if ($isApiRequest) {
        echo json_encode(['success' => false, 'message' => '未登录']);
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

// 7. 权限检查
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== '员工') {
    if ($isApiRequest) {
        echo json_encode(['success' => false, 'message' => '权限不足']);
        exit();
    } else {
        header('Location: home.php');
        exit();
    }
}

// 8. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 初始化数据库连接
$pdo = getPDOConnection();

// 处理用户操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add_user') {
        // 添加用户
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $role = isset($_POST['role']) ? trim($_POST['role']) : '员工';
        
        if (empty($username) || empty($password)) {
            $error = '用户名和密码不能为空';
        } else {
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = '用户名已存在';
            } else {
                try {
                    // 确保数据库连接正常
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 插入新用户（包含status字段）
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 1)");
                    $result = $stmt->execute([$username, $password, $role]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        $_SESSION['success'] = '用户添加成功';
                        header('Location: user_manage.php');
                        exit();
                    } else {
                        $error = '用户添加失败，数据库操作未影响任何行';
                    }
                } catch (PDOException $e) {
                    $error = '数据库操作失败：' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'edit_user') {
        // 编辑用户
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $role = isset($_POST['role']) ? trim($_POST['role']) : '员工';
        
        if ($id > 0 && !empty($username)) {
            // 检查权限：员工只能编辑自己的信息，管理员可以编辑所有信息
            if ($_SESSION['role'] === '员工' && $id != $_SESSION['user_id']) {
                $error = '您只能编辑自己的信息';
            } else {
                try {
                    // 如果是员工编辑自己，只能修改密码，不能修改用户名和角色
                    if ($_SESSION['role'] === '员工') {
                        if (!empty($password)) {
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $result = $stmt->execute([$password, $id]);
                        } else {
                            $error = '密码不能为空';
                        }
                    } else {
                        // 管理员可以编辑所有信息
                        if (!empty($password)) {
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                            $result = $stmt->execute([$username, $password, $role, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                            $result = $stmt->execute([$username, $role, $id]);
                        }
                    }
                    
                    if (isset($result) && $result && $stmt->rowCount() > 0) {
                        $_SESSION['success'] = '用户信息更新成功';
                        header('Location: user_manage.php');
                        exit();
                    } else {
                        $error = '用户信息更新失败，可能数据未发生变化';
                    }
                } catch (PDOException $e) {
                    $error = '数据库操作失败：' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete_user') {
        // 删除用户
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            // 不能删除当前登录用户
            if ($id != $_SESSION['user_id']) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        $_SESSION['success'] = '用户删除成功';
                        header('Location: user_manage.php');
                        exit();
                    } else {
                        $error = '用户删除失败，可能用户不存在';
                    }
                } catch (PDOException $e) {
                    $error = '数据库操作失败：' . $e->getMessage();
                }
            } else {
                $error = '不能删除当前登录用户';
            }
        }
    }
}

// 处理各模块搜索条件
$admin_search = isset($_GET['admin_search']) ? trim($_GET['admin_search']) : '';
$staff_search = isset($_GET['staff_search']) ? trim($_GET['staff_search']) : '';
$user_search = isset($_GET['user_search']) ? trim($_GET['user_search']) : '';

// 分页参数
$admin_page = isset($_GET['admin_page']) ? max(1, intval($_GET['admin_page'])) : 1;
$staff_page = isset($_GET['staff_page']) ? max(1, intval($_GET['staff_page'])) : 1;
$user_page = isset($_GET['user_page']) ? max(1, intval($_GET['user_page'])) : 1;
$per_page = 10;

// 获取管理员用户列表
$admin_where = ["role = 'admin'"];
$admin_params = [];
if (!empty($admin_search)) {
    $admin_where[] = "username LIKE ?";
    $admin_params[] = "%$admin_search%";
}

// 获取管理员总数
$admin_count_sql = "SELECT COUNT(*) FROM users WHERE " . implode(" AND ", $admin_where);
$admin_count_stmt = $pdo->prepare($admin_count_sql);
$admin_count_stmt->execute($admin_params);
$admin_total_count = $admin_count_stmt->fetchColumn();

// 添加分页
$admin_offset = ($admin_page - 1) * $per_page;
$admin_sql = "SELECT * FROM users WHERE " . implode(" AND ", $admin_where) . " ORDER BY create_time DESC LIMIT $per_page OFFSET $admin_offset";
$admin_stmt = $pdo->prepare($admin_sql);
$admin_stmt->execute($admin_params);
$admin_users = $admin_stmt->fetchAll();
$admin_total_pages = ceil($admin_total_count / $per_page);

// 获取员工用户列表
$staff_where = ["role = '员工'"];
$staff_params = [];
if (!empty($staff_search)) {
    $staff_where[] = "username LIKE ?";
    $staff_params[] = "%$staff_search%";
}

// 获取员工总数
$staff_count_sql = "SELECT COUNT(*) FROM users WHERE " . implode(" AND ", $staff_where);
$staff_count_stmt = $pdo->prepare($staff_count_sql);
$staff_count_stmt->execute($staff_params);
$staff_total_count = $staff_count_stmt->fetchColumn();

// 添加分页
$staff_offset = ($staff_page - 1) * $per_page;
$staff_sql = "SELECT * FROM users WHERE " . implode(" AND ", $staff_where) . " ORDER BY create_time DESC LIMIT $per_page OFFSET $staff_offset";
$staff_stmt = $pdo->prepare($staff_sql);
$staff_stmt->execute($staff_params);
$staff_users = $staff_stmt->fetchAll();
$staff_total_pages = ceil($staff_total_count / $per_page);

// 获取普通用户列表
$user_where = ["role = '用户'"];
$user_params = [];
if (!empty($user_search)) {
    $user_where[] = "username LIKE ?";
    $user_params[] = "%$user_search%";
}

// 获取普通用户总数
$user_count_sql = "SELECT COUNT(*) FROM users WHERE " . implode(" AND ", $user_where);
$user_count_stmt = $pdo->prepare($user_count_sql);
$user_count_stmt->execute($user_params);
$user_total_count = $user_count_stmt->fetchColumn();

// 添加分页
$user_offset = ($user_page - 1) * $per_page;
$user_sql = "SELECT * FROM users WHERE " . implode(" AND ", $user_where) . " ORDER BY create_time DESC LIMIT $per_page OFFSET $user_offset";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute($user_params);
$user_users = $user_stmt->fetchAll();
$user_total_pages = ceil($user_total_count / $per_page);

// 获取用户类型汇总数据
$summary_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$summary_stmt = $pdo->query($summary_sql);
$user_summary = $summary_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 初始化汇总数据
$admin_count = isset($user_summary['admin']) ? $user_summary['admin'] : 0;
$staff_count = isset($user_summary['员工']) ? $user_summary['员工'] : 0;
$user_count = isset($user_summary['用户']) ? $user_summary['用户'] : 0;

// 生成分页URL函数
function getAdminPageUrl($page_num) {
    $params = $_GET;
    $params['admin_page'] = $page_num;
    return 'user_manage.php?' . http_build_query($params) . '#admin-section';
}

function getStaffPageUrl($page_num) {
    $params = $_GET;
    $params['staff_page'] = $page_num;
    return 'user_manage.php?' . http_build_query($params) . '#staff-section';
}

function getUserPageUrl($page_num) {
    $params = $_GET;
    $params['user_page'] = $page_num;
    return 'user_manage.php?' . http_build_query($params) . '#user-section';
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>用户管理 - 图书管理系统</title>
    <link rel="stylesheet" href="/admin-book/css/user_manage.css">
    <style>
        .user-management {
            padding: 20px;
        }
        
        .user-form {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.4) 0%, rgba(118, 75, 162, 0.4) 100%);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            margin-bottom: 0px;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
            color: white;
            transition: box-shadow 0.3s ease;
        }
        
        .user-form:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        
        .user-form h3 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.5em;
            font-weight: 600;
            text-align: center;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: rgba(255,255,255,0.9);
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            box-sizing: border-box;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
        }
        
        .form-group input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='rgba(255,255,255,0.8)' d='M6 8l-4-4h8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 12px;
            padding-right: 40px;
            z-index: 1;
            position: relative;
        }
        
        /* 确保选择框选项可见 */
        .form-group select option {
            background: white;
            color: #333;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            border-color: rgba(255,255,255,0.5);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.6);
            border-color: rgba(255,255,255,0.5);
        }
        
        .user-form .btn-primary {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            backdrop-filter: blur(10px);
            width: 100%;
            margin-top: 10px;
        }
        
        .user-form .btn-primary:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.6);
        }
        
        .user-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        
        .user-table:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        
        .user-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th, .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        /* 统一表格列宽 */
        .user-table th:nth-child(1), .user-table td:nth-child(1) {
            width: 80px; /* ID列 */
        }
        
        .user-table th:nth-child(2), .user-table td:nth-child(2) {
            width: 200px; /* 用户名列 */
        }
        
        .user-table th:nth-child(3), .user-table td:nth-child(3) {
            width: 100px; /* 角色列 */
        }
        
        .user-table th:nth-child(4), .user-table td:nth-child(4) {
            width: 150px; /* 创建时间列 */
        }
        
        .user-table th:nth-child(5), .user-table td:nth-child(5) {
            width: 180px; /* 操作列 */
        }
        
        .user-table th {
            background: #f5f5f5;
            font-weight: 500;
        }
        
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background: #f0f9eb;
            color: #67c23a;
            border: 1px solid #e1f3d8;
        }
        
        .alert-error {
            background: #fef0f0;
            color: #f56c6c;
            border: 1px solid #fde2e2;
        }
        
        /* huizong块悬停效果 */
        .huizong:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.25) !important;
            transform: translateY(-4px);
            border-color: rgba(64, 158, 255, 0.3) !important;
            z-index: 2;
        }
    </style>
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
        <div class="random-joke">用户管理</div>
        <div class="user-info">欢迎你，<?php echo $_SESSION['username']; ?>！<a href="logout.php">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="user-management">
            
            
            <!-- 消息提示 -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- 用户表单和汇总左右排版 -->
            <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <!-- 添加用户表单（左侧） -->
                <div class="user-form" style="flex: 1;">
                    <h3>添加新用户</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>密码</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>角色</label>
                            <select name="role">
                                <option value="员工">员工</option>
                                <option value="admin">管理员</option>
                                <option value="用户">用户</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">添加用户</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- 用户类型汇总（右侧） -->
                <div class="huizong" style="flex: 1; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.1); transition: all 0.3s ease; position: relative; z-index: 1;">
                    <h3 style="margin-top: 0; margin-bottom: 25px; color: #333; text-align: center; font-size: 1.5em; font-weight: 600;">用户类型汇总</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; align-items: center; padding: 15px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #409eff;">
                            <div style="flex: 1;">
                                <div style="font-size: 18px; font-weight: 600; color: #409eff;">管理员</div>
                                <div style="color: #666; font-size: 14px;">系统管理员用户</div>
                            </div>
                            <div style="font-size: 28px; font-weight: bold; color: #409eff;"><?php echo $admin_count; ?></div>
                        </div>
                        <div style="display: flex; align-items: center; padding: 15px; background: #f0f9eb; border-radius: 8px; border-left: 4px solid #67c23a;">
                            <div style="flex: 1;">
                                <div style="font-size: 18px; font-weight: 600; color: #67c23a;">员工</div>
                                <div style="color: #666; font-size: 14px;">系统员工用户</div>
                            </div>
                            <div style="font-size: 28px; font-weight: bold; color: #67c23a;"><?php echo $staff_count; ?></div>
                        </div>
                        <div style="display: flex; align-items: center; padding: 15px; background: #fdf6ec; border-radius: 8px; border-left: 4px solid #e6a23c;">
                            <div style="flex: 1;">
                                <div style="font-size: 18px; font-weight: 600; color: #e6a23c;">用户</div>
                                <div style="color: #666; font-size: 14px;">普通注册用户</div>
                            </div>
                            <div style="font-size: 28px; font-weight: bold; color: #e6a23c;"><?php echo $user_count; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 管理员用户模块 -->
            <div class="user-table" style="margin-bottom: 30px;" id="admin-section">
                <h3 style="color: #409eff; border-left: 4px solid #409eff; padding-left: 10px;">管理员用户 (<?php echo $admin_total_count; ?>人)</h3>
                
                <!-- 管理员搜索区域 -->
                <div class="search-area" style="padding: 15px; background: #f0f9ff; border-bottom: 1px solid #409eff;">
                    <form method="get" action="user_manage.php#admin-section" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="staff_search" value="<?php echo htmlspecialchars($staff_search); ?>">
                        <input type="hidden" name="user_search" value="<?php echo htmlspecialchars($user_search); ?>">
                        <input type="hidden" name="admin_page" value="1">
                        <div style="width: 200px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #409eff;">查询管理员用户名</label>
                            <input type="text" name="admin_search" placeholder="请输入管理员用户名" 
                                   value="<?php echo htmlspecialchars($admin_search); ?>" 
                                   style="width: 100%; padding: 8px; border: 1px solid #409eff; border-radius: 4px;">
                        </div>
                        <div style="margin-top: 22px;">
                            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; background: #409eff;">搜索</button>
                            <?php if (!empty($admin_search)): ?>
                            <a href="user_manage.php?staff_search=<?php echo urlencode($staff_search); ?>&user_search=<?php echo urlencode($user_search); ?>&admin_page=1#admin-section" class="btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">重置</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($admin_users) > 0): ?>
                            <?php foreach ($admin_users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['create_time'])); ?></td>
                                <td>
                                    <?php if ($_SESSION['role'] === 'admin' || $user['id'] == $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-primary" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['role']; ?>')">编辑</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role'] === 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                                    <?php if (!empty($admin_search)): ?>
                                        搜索的管理员 "<?php echo htmlspecialchars($admin_search); ?>" 不存在
                                    <?php else: ?>
                                        暂无管理员用户
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- 员工分页 -->
                <?php if ($staff_total_pages > 1): ?>
                <div class="pagination" style="padding: 15px; background: #f5f5f5; border-top: 1px solid #eee;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: #666; font-size: 14px;">
                            共 <?php echo $staff_total_count; ?> 条记录，第 <?php echo $staff_page; ?> 页/共 <?php echo $staff_total_pages; ?> 页
                        </div>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <a href="<?php echo getStaffPageUrl(1); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&lt;&lt;</a>
                            <a href="<?php echo getStaffPageUrl(max(1, $staff_page - 1)); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&lt;</a>
                            <span style="padding: 0 10px; color: #666;">第</span>
                            <input type="number" id="staff_page_input_2" value="<?php echo $staff_page; ?>" min="1" max="<?php echo $staff_total_pages; ?>" style="width: 60px; padding: 6px; text-align: center; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="padding: 0 10px; color: #666;">页</span>
                            <a href="<?php echo getStaffPageUrl(min($staff_total_pages, $staff_page + 1)); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&gt;</a>
                            <a href="<?php echo getStaffPageUrl($staff_total_pages); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&gt;&gt;</a>
                            <button type="button" onclick="gotoPage('staff', this)" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; border: none; border-radius: 4px; cursor: pointer;">跳转</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 员工用户模块 -->
            <div class="user-table" style="margin-bottom: 30px;" id="staff-section">
                <h3 style="color: #67c23a; border-left: 4px solid #67c23a; padding-left: 10px;">员工用户 (<?php echo $staff_total_count; ?>人)</h3>
                
                <!-- 员工搜索区域 -->
                <div class="search-area" style="padding: 15px; background: #f0f9eb; border-bottom: 1px solid #67c23a;">
                    <form method="get" action="user_manage.php#staff-section" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="admin_search" value="<?php echo htmlspecialchars($admin_search); ?>">
                        <input type="hidden" name="user_search" value="<?php echo htmlspecialchars($user_search); ?>">
                        <input type="hidden" name="staff_page" value="1">
                        <div style="width: 200px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #67c23a;">查询员工用户名</label>
                            <input type="text" name="staff_search" placeholder="请输入员工用户名" 
                                   value="<?php echo htmlspecialchars($staff_search); ?>" 
                                   style="width: 100%; padding: 8px; border: 1px solid #67c23a; border-radius: 4px;">
                        </div>
                        <div style="margin-top: 22px;">
                            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; background: #67c23a;">搜索</button>
                            <?php if (!empty($staff_search)): ?>
                            <a href="user_manage.php?admin_search=<?php echo urlencode($admin_search); ?>&user_search=<?php echo urlencode($user_search); ?>&staff_page=1#staff-section" class="btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">重置</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($staff_users) > 0): ?>
                            <?php foreach ($staff_users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['create_time'])); ?></td>
                                <td>
                                    <?php if ($_SESSION['role'] === 'admin' || $user['id'] == $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-primary" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['role']; ?>')">编辑</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role'] === 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                                    <?php if (!empty($staff_search)): ?>
                                        搜索的员工 "<?php echo htmlspecialchars($staff_search); ?>" 不存在
                                    <?php else: ?>
                                        暂无员工用户
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- 员工分页 -->
                <?php if ($staff_total_pages > 1): ?>
                <div class="pagination" style="padding: 15px; background: #f5f5f5; border-top: 1px solid #eee;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: #666; font-size: 14px;">
                            共 <?php echo $staff_total_count; ?> 条记录，第 <?php echo $staff_page; ?> 页/共 <?php echo $staff_total_pages; ?> 页
                        </div>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <a href="<?php echo getStaffPageUrl(1); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&lt;&lt;</a>
                            <a href="<?php echo getStaffPageUrl(max(1, $staff_page - 1)); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&lt;</a>
                            <span style="padding: 0 10px; color: #666;">第</span>
                            <input type="number" id="staff_page_input_2" value="<?php echo $staff_page; ?>" min="1" max="<?php echo $staff_total_pages; ?>" style="width: 60px; padding: 6px; text-align: center; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="padding: 0 10px; color: #666;">页</span>
                            <a href="<?php echo getStaffPageUrl(min($staff_total_pages, $staff_page + 1)); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&gt;</a>
                            <a href="<?php echo getStaffPageUrl($staff_total_pages); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&gt;&gt;</a>
                            <button type="button" onclick="gotoPage('staff', this)" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; border: none; border-radius: 4px; cursor: pointer;">跳转</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 普通用户模块 -->
            <div class="user-table" style="margin-bottom: 30px;" id="user-section">
                <h3 style="color: #e6a23c; border-left: 4px solid #e6a23c; padding-left: 10px;">普通用户 (<?php echo $user_total_count; ?>人)</h3>
                
                <!-- 普通用户搜索区域 -->
                <div class="search-area" style="padding: 15px; background: #fdf6ec; border-bottom: 1px solid #e6a23c;">
                    <form method="get" action="user_manage.php#user-section" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="admin_search" value="<?php echo htmlspecialchars($admin_search); ?>">
                        <input type="hidden" name="staff_search" value="<?php echo htmlspecialchars($staff_search); ?>">
                        <input type="hidden" name="user_page" value="1">
                        <div style="width: 200px;">
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #e6a23c;">查询普通用户名</label>
                            <input type="text" name="user_search" placeholder="请输入普通用户名" 
                                   value="<?php echo htmlspecialchars($user_search); ?>" 
                                   style="width: 100%; padding: 8px; border: 1px solid #e6a23c; border-radius: 4px;">
                        </div>
                        <div style="margin-top: 22px;">
                            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; background: #e6a23c;">搜索</button>
                            <?php if (!empty($user_search)): ?>
                            <a href="user_manage.php?admin_search=<?php echo urlencode($admin_search); ?>&staff_search=<?php echo urlencode($staff_search); ?>&user_page=1#user-section" class="btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">重置</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($user_users) > 0): ?>
                            <?php foreach ($user_users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['create_time'])); ?></td>
                                <td>
                                    <?php if ($_SESSION['role'] === 'admin' || $user['id'] == $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-primary" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['role']; ?>')">编辑</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($_SESSION['role'] === 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                                    <?php if (!empty($user_search)): ?>
                                        搜索的普通用户 "<?php echo htmlspecialchars($user_search); ?>" 不存在
                                    <?php else: ?>
                                        暂无普通用户
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- 员工分页 -->
                <?php if ($staff_total_pages > 1): ?>
                <div class="pagination" style="padding: 15px; background: #f5f5f5; border-top: 1px solid #eee;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="color: #666; font-size: 14px;">
                            共 <?php echo $staff_total_count; ?> 条记录，第 <?php echo $staff_page; ?> 页/共 <?php echo $staff_total_pages; ?> 页
                        </div>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <a href="<?php echo getStaffPageUrl(1); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&lt;&lt;</a>
                            <a href="<?php echo getStaffPageUrl(max(1, $staff_page - 1)); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&lt;</a>
                            <span style="padding: 0 10px; color: #666;">第</span>
                            <input type="number" id="staff_page_input_2" value="<?php echo $staff_page; ?>" min="1" max="<?php echo $staff_total_pages; ?>" style="width: 60px; padding: 6px; text-align: center; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="padding: 0 10px; color: #666;">页</span>
                            <a href="<?php echo getStaffPageUrl(min($staff_total_pages, $staff_page + 1)); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&gt;</a>
                            <a href="<?php echo getStaffPageUrl($staff_total_pages); ?>" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; text-decoration: none; border-radius: 4px;">&gt;&gt;</a>
                            <button type="button" onclick="gotoPage('staff', this)" class="btn" style="padding: 6px 12px; background: #67c23a; color: white; border: none; border-radius: 4px; cursor: pointer;">跳转</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 编辑用户模态框 -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; width: 400px;">
            <h3>编辑用户</h3>
            <form method="post" id="editForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="editUserId">
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" id="editUsername" required>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" id="editUsernameDisplay" readonly style="background-color: #f5f5f5;">
                    <input type="hidden" name="username" id="editUsername">
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>密码<?php echo $_SESSION['role'] === '员工' ? '' : '（留空则不修改）'; ?></label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="editPassword" style="padding-right: 40px;" <?php echo $_SESSION['role'] === '员工' ? 'required' : ''; ?>>
                        <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;">👁️</span>
                    </div>
                </div>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="form-group">
                    <label>角色</label>
                    <select name="role" id="editRole">
                        <option value="员工">员工</option>
                        <option value="admin">管理员</option>
                        <option value="用户">用户</option>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="role" id="editRole">
                <?php endif; ?>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editUser(id, username, role) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editUsername').value = username;
            document.getElementById('editRole').value = role;
            document.getElementById('editPassword').value = '';
            
            // 如果是员工用户，显示只读的用户名
            <?php if ($_SESSION['role'] === '员工'): ?>
            document.getElementById('editUsernameDisplay').value = username;
            <?php endif; ?>
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        // 删除确认功能
        function confirmDelete(id, username) {
            if (confirm('确定要删除用户 "' + username + '" 吗？')) {
                // 创建表单并提交
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_user';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // 密码显示/隐藏功能
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('editPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // 点击模态框外部关闭
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // 页面跳转功能
        function gotoPage(type, button) {
            // 找到按钮附近的输入框
            const paginationDiv = button.closest('.pagination');
            if (!paginationDiv) {
                alert('页面元素加载异常，请刷新页面重试');
                return;
            }
            
            const input = paginationDiv.querySelector('input[type="number"]');
            if (!input) {
                alert('找不到页码输入框');
                return;
            }
            
            const page = parseInt(input.value);
            const maxPage = parseInt(input.max);
            
            // 验证页码有效性
            if (isNaN(page) || page < 1 || page > maxPage) {
                alert('请输入有效的页码（1-' + maxPage + '）');
                input.focus();
                return;
            }
            
            // 获取当前URL的所有参数
            const url = new URL(window.location.href);
            url.searchParams.set(type + '_page', page);
            // 设置锚点，确保跳转后停留在对应模块
            url.hash = type + '-section';
            window.location.href = url.toString();
        }
        
        // 页面加载完成后初始化事件
        document.addEventListener('DOMContentLoaded', function() {
            // 为输入框添加回车键支持
            const inputs = document.querySelectorAll('.pagination input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        // 找到对应的按钮
                        const button = this.closest('.pagination').querySelector('button[onclick*="gotoPage"]');
                        if (button) {
                            // 从按钮的onclick属性中提取类型
                            const onclickText = button.getAttribute('onclick');
                            const match = onclickText.match(/gotoPage\('([^']+)'/);
                            if (match) {
                                const type = match[1];
                                gotoPage(type, button);
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
