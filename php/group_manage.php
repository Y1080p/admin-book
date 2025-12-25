<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 检查用户权限（管理员和员工可以访问群聊管理）
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== '员工') {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1>";
    echo "<p>您没有权限访问群聊管理页面。</p>";
    echo "<p><a href='home.php'>返回首页</a></p>";
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

$pdo = getPDOConnection();

// 处理增删改查操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 新增群聊
    if (isset($_POST['add_group'])) {
        $group_name = $_POST['group_name'];
        $group_owner_id = $_POST['group_owner_id'];
        $description = $_POST['description'];
        $max_members = $_POST['max_members'];
        
        $sql = "INSERT INTO chat_groups (group_name, group_owner_id, description, max_members) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_name, $group_owner_id, $description, $max_members]);
        
        header('Location: group_manage.php');
        exit();
    }
    
    // 修改群聊
    if (isset($_POST['edit_group'])) {
        $id = $_POST['id'];
        $group_name = $_POST['group_name'];
        $group_owner_id = $_POST['group_owner_id'];
        $description = $_POST['description'];
        $max_members = $_POST['max_members'];
        
        $sql = "UPDATE chat_groups SET group_name = ?, group_owner_id = ?, description = ?, max_members = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_name, $group_owner_id, $description, $max_members, $id]);
        
        header('Location: group_manage.php');
        exit();
    }
    
    // 删除群聊
    if (isset($_POST['delete_group'])) {
        $id = $_POST['id'];
        
        $sql = "DELETE FROM chat_groups WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        header('Location: group_manage.php');
        exit();
    }
    
    // 批量删除
    if (isset($_POST['batch_delete'])) {
        $ids = $_POST['group_ids'];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM chat_groups WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
        }
        
        header('Location: group_manage.php');
        exit();
    }
    
    // 切换状态（AJAX处理）
    if (isset($_POST['toggle_status']) && isset($_POST['ajax'])) {
        $id = $_POST['id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $sql = "UPDATE chat_groups SET status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$new_status, $id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'newStatus' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => '状态更新失败']);
        }
        exit();
    }
}

// 查询用户列表（用于选择群主）
$users_sql = "SELECT id, username FROM users WHERE status = 1";
$users_stmt = $pdo->query($users_sql);
$users = $users_stmt->fetchAll();

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$offset = ($page - 1) * $per_page;

// 获取搜索参数
$max_members_filter = isset($_GET['max_members']) ? $_GET['max_members'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$owner_name_filter = isset($_GET['owner_name']) ? trim($_GET['owner_name']) : '';
$group_name_filter = isset($_GET['group_name']) ? trim($_GET['group_name']) : '';

// 查询群聊数据
$sql = "SELECT cg.*, u.username as owner_name 
        FROM chat_groups cg 
        LEFT JOIN users u ON cg.group_owner_id = u.id WHERE 1=1";

$count_sql = "SELECT COUNT(*) FROM chat_groups cg 
              LEFT JOIN users u ON cg.group_owner_id = u.id WHERE 1=1";

// 添加搜索条件
if (!empty($max_members_filter)) {
    $sql .= " AND cg.max_members <= ?";
    $count_sql .= " AND cg.max_members <= ?";
}

if ($status_filter !== '') {
    $sql .= " AND cg.status = ?";
    $count_sql .= " AND cg.status = ?";
}

if (!empty($owner_name_filter)) {
    $sql .= " AND u.username LIKE ?";
    $count_sql .= " AND u.username LIKE ?";
}

if (!empty($group_name_filter)) {
    $sql .= " AND cg.group_name LIKE ?";
    $count_sql .= " AND cg.group_name LIKE ?";
}

// 获取总数
$params = [];
if (!empty($max_members_filter)) $params[] = intval($max_members_filter);
if ($status_filter !== '') $params[] = intval($status_filter);
if (!empty($owner_name_filter)) $params[] = "%$owner_name_filter%";
if (!empty($group_name_filter)) $params[] = "%$group_name_filter%";

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_count = $stmt->fetchColumn();

// 添加分页
$sql .= " LIMIT $per_page OFFSET $offset";

// 执行查询
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$groups = $stmt->fetchAll();

// 计算总页数
$total_pages = ceil($total_count / $per_page);

// 生成分页URL函数
function getPageUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'group_manage.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>群聊管理 - 后台管理</title>
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
        
        /* 搜索下拉框样式 */
        .search-select {
            position: relative;
            width: 100%;
        }
        
        .search-select input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .search-select .dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-select .dropdown-item {
            padding: 8px;
            cursor: pointer;
        }
        
        .search-select .dropdown-item:hover {
            background-color: #f5f5f5;
        }
        
        /* 按钮样式 */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.6);
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
            <li><a href="user_manage.php"><i class="icon fa fa-user"></i>用户管理</a></li>
            <li><a href="book_manage.php"><i class="icon fa fa-book"></i>图书管理</a></li>
            <li><a href="category_manage.php"><i class="icon fa fa-list"></i>分类管理</a></li>
            <li><a href="tag_manage.php"><i class="icon fa fa-tags"></i>标签管理</a></li>
            <li><a href="group_manage.php" class="active"><i class="icon fa fa-comments"></i>群聊管理</a></li>
        </ul>
    </div>

    <!-- 顶部栏 -->
    <div class="topbar">
        <div class="random-joke">群聊管理</div>
        <div class="user-info">欢迎你，<?php echo $_SESSION['username']; ?>！<a href="logout.php">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="container">
        <h1>群聊管理</h1>
        
        <!-- 搜索和筛选区域 -->
        <div class="search-area">
            <form method="get" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>群聊名称：</label>
                        <input type="text" name="group_name" value="<?php echo htmlspecialchars($group_name_filter); ?>" placeholder="输入群聊名称">
                    </div>
                    <div class="form-group">
                        <label>群主名称：</label>
                        <input type="text" name="owner_name" value="<?php echo htmlspecialchars($owner_name_filter); ?>" placeholder="输入群主名称">
                    </div>
                    <div class="form-group">
                        <label>最大成员数：</label>
                        <input type="number" name="max_members" value="<?php echo htmlspecialchars($max_members_filter); ?>" placeholder="最大成员数">
                    </div>
                    <div class="form-group">
                        <label>状态：</label>
                        <select name="status">
                            <option value="">全部</option>
                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>启用</option>
                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>禁用</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="group_manage.php" class="btn">重置</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- 操作按钮区域 -->
        <div class="btn-group">
            <button class="add-btn" onclick="showAddModal()">+ 新增群聊</button>
            <button class="del-btn" onclick="batchDelete()">批量删除</button>
        </div>
        
        <!-- 数据表格 -->
        <table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all" onchange="toggleSelectAll(this)"></th>
                    <th>ID</th>
                    <th>群聊名称</th>
                    <th>群主</th>
                    <th>描述</th>
                    <th>最大成员数</th>
                    <th>当前成员数</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $group): ?>
                <tr>
                    <td><input type="checkbox" name="group_ids[]" value="<?php echo $group['id']; ?>"></td>
                    <td><?php echo $group['id']; ?></td>
                    <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                    <td><?php echo htmlspecialchars($group['owner_name']); ?></td>
                    <td><?php echo htmlspecialchars($group['description']); ?></td>
                    <td><?php echo $group['max_members']; ?></td>
                    <td><?php echo isset($group['current_members']) ? $group['current_members'] : 0; ?></td>
                    <td>
                        <button class="status-switch <?php echo $group['status'] ? 'on' : ''; ?>" data-group-id="<?php echo $group['id']; ?>" data-status="<?php echo $group['status']; ?>"></button>
                    </td>
                    <td><?php echo $group['create_time']; ?></td>
                    <td>
                        <a href="javascript:void(0);" onclick="showEditModal(<?php echo $group['id']; ?>, '<?php echo addslashes($group['group_name']); ?>', <?php echo $group['group_owner_id']; ?>, '<?php echo addslashes($group['description']); ?>', <?php echo $group['max_members']; ?>)">修改</a>
                        <a href="javascript:void(0);" onclick="confirmDeleteGroup(<?php echo $group['id']; ?>, '<?php echo addslashes($group['group_name']); ?>')">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- 分页 -->
        <div class="pagination">
            共 <?php echo $total_count; ?> 条 
            <select onchange="changePerPage(this.value)">
                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10条/页</option>
                <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20条/页</option>
                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50条/页</option>
            </select>
            <a href="<?php echo getPageUrl(1); ?>">&lt;&lt;</a>
            <?php if ($page > 1): ?>
                <a href="<?php echo getPageUrl($page - 1); ?>">&lt;</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="<?php echo getPageUrl($i); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="<?php echo getPageUrl($page + 1); ?>">&gt;</a>
            <?php endif; ?>
            <a href="<?php echo getPageUrl($total_pages); ?>">&gt;&gt;</a>
            
            <span>跳转到第 <input type="number" id="gotoPage" min="1" max="<?php echo $total_pages; ?>" value="<?php echo $page; ?>"> 页</span>
            <button onclick="gotoPage()">跳转</button>
        </div>
    </div>
    
    <!-- 新增群聊模态框 -->
    <div class="modal-overlay" id="addModal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>新增群聊</h3>
                <button class="modal-close" onclick="hideAddModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" id="addForm">
                    <div class="form-group">
                        <label>群聊名称：</label>
                        <input type="text" name="group_name" required>
                    </div>
                    <div class="form-group">
                        <label>群主：</label>
                        <div class="search-select">
                            <input type="text" id="add_owner_search" placeholder="搜索用户..." oninput="searchUsers('add')">
                            <div class="dropdown" id="add_owner_dropdown">
                                <?php foreach ($users as $user): ?>
                                    <div class="dropdown-item" onclick="selectUser('add', <?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="group_owner_id" id="add_group_owner_id">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>描述：</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>最大成员数：</label>
                        <input type="number" name="max_members" value="100" min="1">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="hideAddModal()">取消</button>
                        <button type="submit" name="add_group" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 修改群聊模态框 -->
    <div class="modal-overlay" id="editModal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>修改群聊</h3>
                <button class="modal-close" onclick="hideEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" id="editForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label>群聊名称：</label>
                        <input type="text" name="group_name" id="edit_group_name" required>
                    </div>
                    <div class="form-group">
                        <label>群主：</label>
                        <div class="search-select">
                            <input type="text" id="edit_owner_search" placeholder="搜索用户..." oninput="searchUsers('edit')">
                            <div class="dropdown" id="edit_owner_dropdown">
                                <?php foreach ($users as $user): ?>
                                    <div class="dropdown-item" onclick="selectUser('edit', <?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="group_owner_id" id="edit_group_owner_id">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>描述：</label>
                        <textarea name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>最大成员数：</label>
                        <input type="number" name="max_members" id="edit_max_members" min="1">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="hideEditModal()">取消</button>
                        <button type="submit" name="edit_group" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 批量删除表单 -->
    <form method="post" id="batchDeleteForm" style="display: none;">
        <input type="hidden" name="batch_delete" value="1">
    </form>
    
    <script>
        // 确认删除群聊
        function confirmDeleteGroup(groupId, groupName) {
            if (confirm('确定删除群聊 "' + groupName + '" 吗？此操作不可恢复！')) {
                // 创建隐藏表单提交删除请求
                var form = document.createElement('form');
                form.method = 'post';
                form.action = 'group_manage.php';
                
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = groupId;
                
                var deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_group';
                deleteInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // 切换群聊状态
        function toggleGroupStatus(groupId, currentStatus) {
            // 发送AJAX请求更新状态
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'group_manage.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // 更新页面显示
                        var switchElement = document.querySelector('[data-group-id="' + groupId + '"]');
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
            var params = 'toggle_status=1&ajax=1&id=' + groupId + '&current_status=' + currentStatus;
            xhr.send(params);
        }
        
        // 页面加载完成后初始化搜索下拉框
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化新增模态框的搜索下拉框
            initSearchSelect('add_owner_search', 'add_owner_dropdown', 'add_group_owner_id');
            
            // 初始化修改模态框的搜索下拉框
            initSearchSelect('edit_owner_search', 'edit_owner_dropdown', 'edit_group_owner_id');
            
            // 绑定状态切换开关点击事件
            var switches = document.querySelectorAll('.status-switch');
            switches.forEach(function(switchElement) {
                switchElement.addEventListener('click', function() {
                    var groupId = this.getAttribute('data-group-id');
                    var currentStatus = this.getAttribute('data-status');
                    toggleGroupStatus(groupId, currentStatus);
                });
            });
        });
        
        // 显示新增模态框
        function showAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        // 隐藏新增模态框
        function hideAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        // 显示修改模态框
        function showEditModal(groupId, groupName, ownerId, description, maxMembers) {
            document.getElementById('edit_id').value = groupId;
            document.getElementById('edit_group_name').value = groupName;
            document.getElementById('edit_group_owner_id').value = ownerId;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_max_members').value = maxMembers;
            
            // 设置当前选中的用户
            var selectedUser = document.querySelector('[data-user-id="' + ownerId + '"]');
            if (selectedUser) {
                document.getElementById('edit_owner_search').value = selectedUser.textContent;
            }
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        // 隐藏修改模态框
        function hideEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // 搜索用户
        function searchUsers(type) {
            var searchTerm = document.getElementById(type + '_owner_search').value.toLowerCase();
            var dropdown = document.getElementById(type + '_owner_dropdown');
            var items = dropdown.querySelectorAll('.dropdown-item');
            
            items.forEach(function(item) {
                if (item.textContent.toLowerCase().includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            dropdown.style.display = 'block';
        }
        
        // 选择用户
        function selectUser(type, userId, username) {
            document.getElementById(type + '_owner_search').value = username;
            document.getElementById(type + '_group_owner_id').value = userId;
            document.getElementById(type + '_owner_dropdown').style.display = 'none';
        }
        
        // 初始化搜索下拉框
        function initSearchSelect(searchId, dropdownId, hiddenId) {
            var searchInput = document.getElementById(searchId);
            var dropdown = document.getElementById(dropdownId);
            
            searchInput.addEventListener('focus', function() {
                dropdown.style.display = 'block';
            });
            
            // 点击外部关闭下拉框
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
        }
        
        // 全选/取消全选
        function toggleSelectAll(checkbox) {
            var checkboxes = document.querySelectorAll('input[name="group_ids[]"]');
            checkboxes.forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
        }
        
        // 批量删除
        function batchDelete() {
            var selectedGroups = document.querySelectorAll('input[name="group_ids[]"]:checked');
            
            if (selectedGroups.length === 0) {
                alert('请选择要删除的群聊！');
                return;
            }
            
            var groupNames = [];
            selectedGroups.forEach(function(checkbox) {
                var groupName = checkbox.closest('tr').querySelector('td:nth-child(3)').textContent;
                groupNames.push(groupName);
            });
            
            var confirmMessage = '确定要删除以下 ' + selectedGroups.length + ' 个群聊吗？\n\n' + 
                                groupNames.join('\n') + 
                                '\n\n此操作不可恢复！';
            
            if (confirm(confirmMessage)) {
                var form = document.getElementById('batchDeleteForm');
                selectedGroups.forEach(function(checkbox) {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'group_ids[]';
                    hiddenInput.value = checkbox.value;
                    form.appendChild(hiddenInput);
                });
                form.submit();
            }
        }
        
        // 修改每页显示数量
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }
        
        // 页面跳转功能
        function gotoPage() {
            const input = document.getElementById('gotoPage');
            const page = parseInt(input.value);
            const maxPage = <?php echo $total_pages; ?>;
            
            if (isNaN(page) || page < 1 || page > maxPage) {
                alert('请输入有效的页码（1-' + maxPage + '）');
                input.focus();
                return;
            }
            
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
        
        // 为跳转输入框添加回车键支持
        const gotoInput = document.getElementById('gotoPage');
        if (gotoInput) {
            gotoInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    gotoPage();
                }
            });
        }
    </script>
        </div>
    </div>
</body>
</html>