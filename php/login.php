<?php
// 1. 优先处理OPTIONS预请求（解决跨域预请求）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置允许的跨域域名（包含本地和线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 检测是否为API请求（通过请求头或特定参数）
$isApiRequest = false;
if (in_array($origin, $allowedOrigins)) {
    $isApiRequest = true;
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=utf-8');
}

// 4. 启动session
session_start();

// 5. 检查是否已登录
if (isset($_SESSION['user_id'])) {
    if ($isApiRequest) {
        // API请求：返回JSON
        echo json_encode(['success' => false, 'message' => '用户已登录']);
        exit();
    } else {
        // 普通网页请求：重定向
        header('Location: home.php');
        exit();
    }
}

// 6. 数据库连接
require_once '../SQL Connection/db_connect.php';

$error = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!empty($username) && !empty($password)) {
        $pdo = getPDOConnection();
        
        // 先检查用户是否存在
        $sql = "SELECT id, username, role, password FROM users WHERE username = ? AND status = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // 检查密码是否正确
            if ($user['password'] === $password) {
                // 检查角色权限
                if ($user['role'] === '用户') {
                    $error = '该账号无权限登录后台系统！';
                    if ($isApiRequest) {
                        echo json_encode(['success' => false, 'message' => $error]);
                        exit();
                    }
                } else {
                    // 登录成功，设置session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($isApiRequest) {
                        // API请求：返回JSON
                        echo json_encode([
                            'success' => true, 
                            'message' => '登录成功',
                            'user' => [
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'role' => $user['role']
                            ]
                        ]);
                        exit();
                    } else {
                        // 普通请求：重定向
                        header('Location: home.php');
                        exit();
                    }
                }
            } else {
                // 密码错误
                $error = '账号或密码错误！';
                if ($isApiRequest) {
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit();
                }
            }
        } else {
            // 用户不存在
            $error = '账号或密码错误！';
            if ($isApiRequest) {
                echo json_encode(['success' => false, 'message' => $error]);
                exit();
            }
        }
    } else {
        $error = '请输入用户名和密码！';
        if ($isApiRequest) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>登录 - 后台管理系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: #f5f5f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
            background: #ffeaea;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ffcdd2;
        }
        
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #f5f5f0;
            color: #000000;
            border: 1px solid #d4d4d4;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            background: #f0f0e0;
            box-shadow: 0 4px 12px rgba(245, 245, 240, 0.4);
        }
        
        .test-accounts {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .test-accounts h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .test-accounts ul {
            list-style: none;
        }
        
        .test-accounts li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>后台管理系统</h1>
            <p>欢迎登录后台管理系统</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p style="color: #666; font-size: 12px; margin-top: 10px;">
                    您已登录为：<?php echo $_SESSION['username']; ?> 
                    <a href="logout.php" style="color: #667eea; margin-left: 10px;">退出登录</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" placeholder="请输入用户名" required>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" placeholder="请输入密码" required>
            </div>
            
            <button type="submit" class="login-btn">登录</button>
        </form>
        
        <!-- <div class="test-accounts">
            <h3>测试账号</h3>
            <ul>
                <li><strong>管理员账号:</strong> admin / admin123</li>
                <li><strong>员工:</strong> YJM / yjm123</li>
                <li><strong>员工:</strong> 123 / 123456</li>
                <li><strong>员工:</strong> sleepduck / sleep123</li>
            </ul>
        </div> -->
    </div>
</body>
</html>
