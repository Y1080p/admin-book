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
    // 适配管理类接口的增删改查，允许所有常用请求方法
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

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 查询标签数据（支持搜索功能）
$pdo = getPDOConnection();

// 获取搜索参数
$status = isset($_GET['status']) ? $_GET['status'] : '';
$name = isset($_GET['name']) ? trim($_GET['name']) : '';

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$offset = ($page - 1) * $per_page;

// 构建SQL查询
$sql = "SELECT * FROM tags WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM tags WHERE 1=1";
$params = [];

// 添加状态筛选条件
if ($status !== '') {
    $sql .= " AND status = ?";
    $count_sql .= " AND status = " . intval($status);
    $params[] = intval($status);
}

// 添加名称搜索条件
if (!empty($name)) {
    $sql .= " AND name LIKE ?";
    $count_sql .= " AND name LIKE '%" . addslashes($name) . "%'";
    $params[] = "%$name%";
}

$sql .= " ORDER BY create_time DESC";

// 获取总数
$total_count = $pdo->query($count_sql)->fetchColumn();

// 添加分页
$sql .= " LIMIT $per_page OFFSET $offset";

// 执行查询
if (!empty($params)) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tags = $stmt->fetchAll();
} else {
    $stmt = $pdo->query($sql);
    $tags = $stmt->fetchAll();
}

// 计算总页数
$total_pages = ceil($total_count / $per_page);

// 生成分页URL函数
function getPageUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'tag_manage.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>标签管理 - 后台管理</title>
    <link rel="stylesheet" href="/css/user_manage.css">
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
        
        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .modal h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #666;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-actions {
            text-align: right;
            margin-top: 20px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .btn-primary {
            background: #409eff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
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
            <li><a href="tag_manage.php" class="active"><i class="icon fa fa-tags"></i>标签管理</a></li>
            <!-- <li><a href="comment_manage.php"><i class="icon fa fa-comment"></i>评论管理</a></li> -->
            <li><a href="group_manage.php"><i class="icon fa fa-comments"></i>群聊管理</a></li>
        </ul>
    </div>

    <!-- 顶部栏 -->
    <div class="topbar">
        <div class="random-joke">标签管理</div>
        <div class="user-info">欢迎你，<?php echo $_SESSION['username']; ?>！<a href="logout.php">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="search-bar">
            <form method="get" action="tag_manage.php" style="display: flex; gap: 10px; align-items: center;">
                <select name="status">
                    <option value="">全部状态</option>
                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>禁用</option>
                </select>
                <input type="text" name="name" placeholder="标签名称" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                <button type="submit">搜索</button>
                <?php if (isset($_GET['status']) && $_GET['status'] !== '' || isset($_GET['name']) && $_GET['name'] !== ''): ?>
                <a href="tag_manage.php" class="reset-btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer;">重置</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="btn-group">
            <button class="add-btn" onclick="showAddTagModal()">+ 新增标签</button>
            <button class="del-btn" onclick="batchDeleteTags()">批量删除</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                    <th>ID</th>
                    <th>标签名称</th>
                    <th>颜色</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tags as $tag) : ?>
                <tr>
                    <td><input type="checkbox" name="tag_ids" value="<?php echo $tag['id']; ?>"></td>
                    <td><?php echo $tag['id']; ?></td>
                    <td><?php echo $tag['name']; ?></td>
                    <td>
                        <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo $tag['color']; ?>; border-radius: 3px;"></span>
                        <?php echo $tag['color']; ?>
                    </td>
                    <td>
                        <button class="status-switch <?php echo $tag['status'] ? 'on' : ''; ?>" data-tag-id="<?php echo $tag['id']; ?>" data-status="<?php echo $tag['status']; ?>"></button>
                    </td>
                    <td><?php echo $tag['create_time']; ?></td>
                    <td>
                        <a href="#" onclick="editTag(<?php echo $tag['id']; ?>, '<?php echo $tag['name']; ?>', '<?php echo $tag['color']; ?>', <?php echo $tag['status']; ?>)">修改</a>
                        <a href="#" onclick="deleteTag(<?php echo $tag['id']; ?>, '<?php echo $tag['name']; ?>')">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            共 <?php echo $total_count; ?> 条 
            <select onchange="changePerPage(this.value)">
                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10条/页</option>
                <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20条/页</option>
                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50条/页</option>
            </select>
            <a href="<?php echo getPageUrl(1); ?>">&lt;&lt;</a>
            <a href="<?php echo getPageUrl(max(1, $page - 1)); ?>">&lt;</a>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="<?php echo getPageUrl($i); ?>" <?php echo $i == $page ? 'class="current"' : ''; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
            <a href="<?php echo getPageUrl(min($total_pages, $page + 1)); ?>">&gt;</a>
            <a href="<?php echo getPageUrl($total_pages); ?>">&gt;&gt;</a>
            前往 <input type="text" id="gotoPage" value="<?php echo $page; ?>" size="1"> 页
            <button onclick="gotoPage()">跳转</button>
        </div>
    </div>

    <!-- 新增标签模态框 -->
    <div id="addTagModal" class="modal">
        <div class="modal-content">
            <h3>新增标签</h3>
            <form id="addTagForm">
                <div class="form-group">
                    <label>标签名称</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>颜色</label>
                    <input type="color" name="color" value="#409eff" required>
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select name="status">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideAddTagModal()">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddTagForm()">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 修改标签模态框 -->
    <div id="editTagModal" class="modal">
        <div class="modal-content">
            <h3>修改标签</h3>
            <form id="editTagForm">
                <input type="hidden" name="id" id="editTagId">
                <div class="form-group">
                    <label>标签名称</label>
                    <input type="text" name="name" id="editTagName" required>
                </div>
                <div class="form-group">
                    <label>颜色</label>
                    <input type="color" name="color" id="editTagColor" required>
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select name="status" id="editTagStatus">
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideEditTagModal()">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditTagForm()">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 切换标签状态
        function toggleTagStatus(tagId, currentStatus) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_tag_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        var switchElement = document.querySelector('[data-tag-id="' + tagId + '"]');
                        if (response.newStatus == 1) {
                            switchElement.classList.add('on');
                            switchElement.setAttribute('data-status', '1');
                        } else {
                            switchElement.classList.remove('on');
                            switchElement.setAttribute('data-status', '0');
                        }
                    } else {
                        alert('状态更新失败：' + response.message);
                    }
                }
            };
            
            var newStatus = currentStatus == 1 ? 0 : 1;
            var params = 'tag_id=' + tagId + '&status=' + newStatus;
            xhr.send(params);
        }
        
        // 页面加载完成后绑定事件
        document.addEventListener('DOMContentLoaded', function() {
            // 绑定状态切换开关点击事件
            var switches = document.querySelectorAll('.status-switch');
            switches.forEach(function(switchElement) {
                switchElement.addEventListener('click', function() {
                    var tagId = this.getAttribute('data-tag-id');
                    var currentStatus = this.getAttribute('data-status');
                    toggleTagStatus(tagId, currentStatus);
                });
            });
            
            // 绑定模态框外部点击关闭事件
            var addModal = document.getElementById('addTagModal');
            if (addModal) {
                addModal.addEventListener('click', function(e) {
                    if (e.target === addModal) {
                        hideAddTagModal();
                    }
                });
            }
            
            var editModal = document.getElementById('editTagModal');
            if (editModal) {
                editModal.addEventListener('click', function(e) {
                    if (e.target === editModal) {
                        hideEditTagModal();
                    }
                });
            }
        });

        // 显示新增标签模态框
        function showAddTagModal() {
            document.getElementById('addTagModal').style.display = 'flex';
        }

        // 隐藏新增标签模态框
        function hideAddTagModal() {
            document.getElementById('addTagModal').style.display = 'none';
            document.getElementById('addTagForm').reset();
        }

        // 提交新增标签表单
        function submitAddTagForm() {
            var form = document.getElementById('addTagForm');
            var formData = new FormData(form);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_tag.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('标签添加成功！');
                            hideAddTagModal();
                            location.reload();
                        } else {
                            alert('添加失败：' + response.message);
                        }
                    } else {
                        alert('请求失败，请重试');
                    }
                }
            };
            
            xhr.send(formData);
        }

        // 显示修改标签模态框
        function editTag(id, name, color, status) {
            document.getElementById('editTagId').value = id;
            document.getElementById('editTagName').value = name;
            document.getElementById('editTagColor').value = color;
            document.getElementById('editTagStatus').value = status;
            document.getElementById('editTagModal').style.display = 'flex';
        }

        // 隐藏修改标签模态框
        function hideEditTagModal() {
            document.getElementById('editTagModal').style.display = 'none';
        }

        // 提交修改标签表单
        function submitEditTagForm() {
            var form = document.getElementById('editTagForm');
            var formData = new FormData(form);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_tag.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('标签修改成功！');
                            hideEditTagModal();
                            location.reload();
                        } else {
                            alert('修改失败：' + response.message);
                        }
                    } else {
                        alert('请求失败，请重试');
                    }
                }
            };
            
            xhr.send(formData);
        }

        // 删除标签
        function deleteTag(id, name) {
            if (confirm('确定要删除标签 "' + name + '" 吗？此操作不可恢复！')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_tag.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('标签删除成功！');
                            location.reload();
                        } else {
                            alert('删除失败：' + response.message);
                        }
                    }
                };
                
                xhr.send('tag_id=' + id);
            }
        }

        // 全选/取消全选
        function toggleSelectAll(selectAllCheckbox) {
            var checkboxes = document.querySelectorAll('input[name="tag_ids"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        // 批量删除标签
        function batchDeleteTags() {
            var selectedTags = document.querySelectorAll('input[name="tag_ids"]:checked');
            
            if (selectedTags.length === 0) {
                alert('请选择要删除的标签！');
                return;
            }
            
            var tagIds = [];
            var tagNames = [];
            
            selectedTags.forEach(function(checkbox) {
                var row = checkbox.closest('tr');
                var tagId = checkbox.value;
                var tagName = row.cells[2].textContent;
                tagIds.push(tagId);
                tagNames.push(tagName);
            });
            
            var confirmMessage = '确定要删除以下 ' + selectedTags.length + ' 个标签吗？\n\n' + 
                                tagNames.join('\n') + 
                                '\n\n此操作不可恢复！';
            
            if (confirm(confirmMessage)) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'batch_delete_tags.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('批量删除成功！共删除 ' + selectedTags.length + ' 个标签');
                            location.reload();
                        } else {
                            alert('批量删除失败：' + response.message);
                        }
                    }
                };
                
                xhr.send('tag_ids=' + JSON.stringify(tagIds));
            }
        }

        // 分页功能
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.set('page', 1); // 重置到第一页
            window.location.href = url.toString();
        }

        function gotoPage() {
            const pageInput = document.getElementById('gotoPage');
            const page = parseInt(pageInput.value);
            if (page >= 1 && page <= <?php echo $total_pages; ?>) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            } else {
                alert('请输入有效的页码（1-<?php echo $total_pages; ?>）');
                pageInput.focus();
            }
        }

        // 回车跳转
        document.getElementById('gotoPage').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                gotoPage();
            }
        });
    </script>
</body>
</html>
