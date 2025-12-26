<?php
// 1. 优先处理OPTIONS预请求（解决跨域预请求拦截）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. 配置信任的跨域域名（包含本地开发和线上前端）
$allowedOrigins = [
    'http://localhost:3005', 
    'http://127.0.0.1:3005',
    'https://stunning-biscochitos-49d12b.netlify.app' // 你的线上前端域名
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 3. 仅给信任域名返回CORS头（安全规范）
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    // 图书管理接口涉及增删改查，允许所有常用请求方法
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// 4. 设置统一响应格式（适配JSON返回）
header('Content-Type: application/json; charset=utf-8');

// 5. 原有session逻辑（移到CORS之后，避免报错）
session_start();

// 6. 原有登录检查逻辑（完全保留）
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 7. 原有数据库连接逻辑（完全保留）
require_once '../SQL Connection/db_connect.php';

// 查询分类数据（支持搜索功能）
$pdo = getPDOConnection();

// 获取搜索参数
$status = isset($_GET['status']) ? $_GET['status'] : '';
$name = isset($_GET['name']) ? trim($_GET['name']) : '';

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$offset = ($page - 1) * $per_page;

// 构建SQL查询
$sql = "SELECT * FROM categories WHERE 1=1";
$params = [];

// 添加状态筛选条件
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = intval($status);
}

// 添加名称搜索条件
if (!empty($name)) {
    $sql .= " AND name LIKE ?";
    $params[] = "%$name%";
}

$sql .= " ORDER BY sort_order ASC, create_time DESC";

// 获取总数
$count_sql = "SELECT COUNT(*) FROM categories WHERE 1=1";
if ($status !== '') {
    $count_sql .= " AND status = " . intval($status);
}
if (!empty($name)) {
    $count_sql .= " AND name LIKE '%" . addslashes($name) . "%'";
}
$total_count = $pdo->query($count_sql)->fetchColumn();

// 添加分页
$sql .= " LIMIT $per_page OFFSET $offset";

// 执行查询
if (!empty($params)) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
} else {
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll();
}

// 计算总页数
$total_pages = ceil($total_count / $per_page);

// 生成分页URL函数
function getPageUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'category_manage.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>分类管理 - 后台管理</title>
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
    <script>
        // 切换分类状态
        function toggleCategoryStatus(categoryId, currentStatus) {
            // 发送AJAX请求更新状态
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_category_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // 更新页面显示
                        var switchElement = document.querySelector('[data-category-id="' + categoryId + '"]');
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
            var params = 'category_id=' + categoryId + '&status=' + newStatus;
            xhr.send(params);
        }
        
        // 页面加载完成后绑定点击事件
        document.addEventListener('DOMContentLoaded', function() {
            // 绑定状态切换开关点击事件
            var switches = document.querySelectorAll('.status-switch');
            switches.forEach(function(switchElement) {
                switchElement.addEventListener('click', function() {
                    var categoryId = this.getAttribute('data-category-id');
                    var currentStatus = this.getAttribute('data-status');
                    toggleCategoryStatus(categoryId, currentStatus);
                });
            });
        });

        // 显示新增分类对话框
        function showAddCategoryModal() {
            var modal = document.getElementById('addCategoryModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
            }
        }

        // 隐藏新增分类对话框
        function hideAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'none';
            document.getElementById('addCategoryForm').reset();
        }

        // 提交新增分类表单
        function submitAddCategoryForm() {
            var form = document.getElementById('addCategoryForm');
            var formData = new FormData(form);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_category.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('分类添加成功！');
                            hideAddCategoryModal();
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

        // 显示修改分类对话框
        function showEditCategoryModal(categoryId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_category.php?id=' + categoryId, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        var category = response.category;
                        
                        document.getElementById('editCategoryId').value = category.id;
                        document.getElementById('editName').value = category.name;
                        document.getElementById('editDescription').value = category.description || '';
                        document.getElementById('editSortOrder').value = category.sort_order;
                        document.getElementById('editStatus').value = category.status;
                        
                        document.getElementById('editCategoryModal').style.display = 'flex';
                    } else {
                        alert('获取分类信息失败：' + response.message);
                    }
                }
            };
            
            xhr.send();
        }

        // 隐藏修改分类对话框
        function hideEditCategoryModal() {
            document.getElementById('editCategoryModal').style.display = 'none';
        }

        // 提交修改分类表单
        function submitEditCategoryForm() {
            var form = document.getElementById('editCategoryForm');
            var formData = new FormData(form);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_category.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('分类修改成功！');
                            hideEditCategoryModal();
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

        // 删除分类
        function deleteCategory(categoryId, categoryName) {
            if (confirm('确定要删除分类《' + categoryName + '》吗？此操作不可恢复！')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_category.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('分类删除成功！');
                            location.reload();
                        } else {
                            alert('删除失败：' + response.message);
                        }
                    }
                };
                
                xhr.send('category_id=' + categoryId);
            }
        }

        // 全选/取消全选
        function toggleSelectAll(selectAllCheckbox) {
            var checkboxes = document.querySelectorAll('input[name="category_ids"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        // 批量删除分类
        function batchDeleteCategories() {
            var selectedCategories = document.querySelectorAll('input[name="category_ids"]:checked');
            
            if (selectedCategories.length === 0) {
                alert('请选择要删除的分类！');
                return;
            }
            
            var categoryIds = [];
            var categoryNames = [];
            
            selectedCategories.forEach(function(checkbox) {
                var row = checkbox.closest('tr');
                var categoryId = checkbox.value;
                var categoryName = row.cells[2].textContent;
                categoryIds.push(categoryId);
                categoryNames.push(categoryName);
            });
            
            var confirmMessage = '确定要删除以下 ' + selectedCategories.length + ' 个分类吗？\n\n' + 
                                categoryNames.join('\n') + 
                                '\n\n此操作不可恢复！';
            
            if (confirm(confirmMessage)) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'batch_delete_categories.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('批量删除成功！共删除 ' + selectedCategories.length + ' 个分类');
                            location.reload();
                        } else {
                            alert('批量删除失败：' + response.message);
                        }
                    }
                };
                
                xhr.send('category_ids=' + JSON.stringify(categoryIds));
            }
        }
    </script>
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
            <li><a href="category_manage.php" class="active"><i class="icon fa fa-list"></i>分类管理</a></li>
            <li><a href="tag_manage.php"><i class="icon fa fa-tags"></i>标签管理</a></li>
            <!-- <li><a href="comment_manage.php"><i class="icon fa fa-comment"></i>评论管理</a></li> -->
            <li><a href="group_manage.php"><i class="icon fa fa-comments"></i>群聊管理</a></li>
        </ul>
    </div>

    <!-- 顶部栏 -->
    <div class="topbar">
        <div class="random-joke">分类管理</div>
        <div class="user-info">欢迎你！<a href="#">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="search-bar">
            <form method="get" action="category_manage.php" style="display: flex; gap: 10px; align-items: center;">
                <select name="status">
                    <option value="">全部状态</option>
                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>禁用</option>
                </select>
                <input type="text" name="name" placeholder="分类名称" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                <button type="submit">搜索</button>
                <?php if (isset($_GET['status']) && $_GET['status'] !== '' || isset($_GET['name']) && $_GET['name'] !== ''): ?>
                <a href="category_manage.php" class="reset-btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer;">重置</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="btn-group">
            <button class="add-btn" onclick="showAddCategoryModal()">+ 新增分类</button>
            <!-- <button class="edit-btn">修改</button> -->
            <button class="del-btn" onclick="batchDeleteCategories()">批量删除</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                    <th>ID</th>
                    <th>分类名称</th>
                    <th>描述</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category) : ?>
                <tr>
                    <td><input type="checkbox" name="category_ids" value="<?php echo $category['id']; ?>"></td>
                    <td><?php echo $category['id']; ?></td>
                    <td><?php echo $category['name']; ?></td>
                    <td><?php echo isset($category['description']) ? $category['description'] : ''; ?></td>
                    <td><?php echo $category['sort_order']; ?></td>
                    <td>
                        <button class="status-switch <?php echo $category['status'] ? 'on' : ''; ?>" data-category-id="<?php echo $category['id']; ?>" data-status="<?php echo $category['status']; ?>"></button>
                    </td>
                    <td><?php echo $category['create_time']; ?></td>
                    <td>
                        <a href="javascript:void(0);" onclick="showEditCategoryModal(<?php echo $category['id']; ?>)">修改</a>
                        <a href="javascript:void(0);" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')">删除</a>
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

    <script>
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
