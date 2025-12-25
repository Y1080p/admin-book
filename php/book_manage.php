<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 引入数据库连接函数
require_once '../SQL Connection/db_connect.php';

// 查询图书数据（支持搜索功能）
$pdo = getPDOConnection();

// 获取搜索参数
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$title = isset($_GET['title']) ? trim($_GET['title']) : '';
$author = isset($_GET['author']) ? trim($_GET['author']) : '';

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$offset = ($page - 1) * $per_page;

// 构建SQL查询
$sql = "SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
$params = [];

// 添加分类筛选条件
if (!empty($category_id)) {
    $sql .= " AND b.category_id = ?";
    $params[] = intval($category_id);
}

// 添加状态筛选条件
if ($status !== '') {
    $sql .= " AND b.status = ?";
    $params[] = intval($status);
}

// 添加标题搜索条件
if (!empty($title)) {
    $sql .= " AND b.title LIKE ?";
    $params[] = "%$title%";
}

// 添加作者搜索条件
if (!empty($author)) {
    $sql .= " AND b.author LIKE ?";
    $params[] = "%$author%";
}

$sql .= " ORDER BY b.create_time DESC";

// 获取总数
$count_sql = "SELECT COUNT(*) FROM books b WHERE 1=1";
if (!empty($category_id)) {
    $count_sql .= " AND b.category_id = " . intval($category_id);
}
if ($status !== '') {
    $count_sql .= " AND b.status = " . intval($status);
}
if (!empty($title)) {
    $count_sql .= " AND b.title LIKE '%" . addslashes($title) . "%'";
}
if (!empty($author)) {
    $count_sql .= " AND b.author LIKE '%" . addslashes($author) . "%'";
}
$total_count = $pdo->query($count_sql)->fetchColumn();

// 添加分页
$sql .= " LIMIT $per_page OFFSET $offset";

// 执行查询
if (!empty($params)) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} else {
    $stmt = $pdo->query($sql);
    $books = $stmt->fetchAll();
}

// 计算总页数
$total_pages = ceil($total_count / $per_page);

// 生成分页URL函数
function getPageUrl($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'book_manage.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>图书管理 - 后台管理</title>
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
        // 切换图书状态
        function toggleBookStatus(bookId, currentStatus) {
            // 发送AJAX请求更新状态
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_book_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // 更新页面显示
                        var switchElement = document.querySelector('[data-book-id="' + bookId + '"]');
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
            var params = 'book_id=' + bookId + '&status=' + newStatus;
            xhr.send(params);
        }
        
        // 页面加载完成后绑定点击事件
        document.addEventListener('DOMContentLoaded', function() {
            // 绑定状态切换开关点击事件
            var switches = document.querySelectorAll('.status-switch');
            switches.forEach(function(switchElement) {
                switchElement.addEventListener('click', function() {
                    var bookId = this.getAttribute('data-book-id');
                    var currentStatus = this.getAttribute('data-status');
                    toggleBookStatus(bookId, currentStatus);
                });
            });
            
            // 绑定对话框外部点击关闭事件
            var addModal = document.getElementById('addBookModal');
            if (addModal) {
                addModal.addEventListener('click', function(e) {
                    if (e.target === addModal) {
                        hideAddBookModal();
                    }
                });
            }
            
            var editModal = document.getElementById('editBookModal');
            if (editModal) {
                editModal.addEventListener('click', function(e) {
                    if (e.target === editModal) {
                        hideEditBookModal();
                    }
                });
            }
        });

        // 显示新增图书对话框
        function showAddBookModal() {
            console.log('showAddBookModal函数被调用');
            var modal = document.getElementById('addBookModal');
            if (modal) {
                console.log('找到对话框元素，当前display值为:', modal.style.display);
                
                // 强制设置样式，确保对话框显示
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                
                console.log('设置display为flex后，当前display值为:', modal.style.display);
                console.log('对话框的classList:', modal.classList);
                console.log('对话框的offsetWidth:', modal.offsetWidth);
                console.log('对话框的offsetHeight:', modal.offsetHeight);
                
                // 强制重绘
                modal.offsetHeight;
            } else {
                console.log('未找到对话框元素');
            }
        }

        // 隐藏新增图书对话框
        function hideAddBookModal() {
            document.getElementById('addBookModal').style.display = 'none';
            // 清空表单
            document.getElementById('addBookForm').reset();
        }

        // 提交新增图书表单
        function submitAddBookForm() {
            var form = document.getElementById('addBookForm');
            var formData = new FormData(form);
            
            // 发送AJAX请求
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_book.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('图书添加成功！');
                            hideAddBookModal();
                            // 刷新页面
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

        // 显示修改图书对话框
        function showEditBookModal(bookId) {
            // 获取图书数据
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_book.php?id=' + bookId, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        var book = response.book;
                        
                        // 填充表单数据
                        document.getElementById('editBookId').value = book.id;
                        document.getElementById('editTitle').value = book.title;
                        document.getElementById('editAuthor').value = book.author;
                        document.getElementById('editIsbn').value = book.isbn || '';
                        document.getElementById('editPublisher').value = book.publisher || '';
                        document.getElementById('editCategoryId').value = book.category_id;
                        document.getElementById('editStatus').value = book.status;
                        document.getElementById('editPrice').value = book.price;
                        document.getElementById('editStock').value = book.stock;
                        
                        // 显示对话框
                        document.getElementById('editBookModal').style.display = 'flex';
                    } else {
                        alert('获取图书信息失败：' + response.message);
                    }
                }
            };
            
            xhr.send();
        }

        // 隐藏修改图书对话框
        function hideEditBookModal() {
            document.getElementById('editBookModal').style.display = 'none';
        }

        // 提交修改图书表单
        function submitEditBookForm() {
            var form = document.getElementById('editBookForm');
            var formData = new FormData(form);
            
            // 发送AJAX请求
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_book.php', true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('图书修改成功！');
                            hideEditBookModal();
                            // 刷新页面
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

        // 删除图书
        function deleteBook(bookId, bookTitle) {
            if (confirm('确定要删除图书《' + bookTitle + '》吗？此操作不可恢复！')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_book.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('图书删除成功！');
                            // 刷新页面
                            location.reload();
                        } else {
                            alert('删除失败：' + response.message);
                        }
                    }
                };
                
                xhr.send('book_id=' + bookId);
            }
        }

        // 全选/取消全选
        function toggleSelectAll(selectAllCheckbox) {
            var checkboxes = document.querySelectorAll('input[name="book_ids"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }

        // 批量删除图书
        function batchDeleteBooks() {
            var selectedBooks = document.querySelectorAll('input[name="book_ids"]:checked');
            
            if (selectedBooks.length === 0) {
                alert('请选择要删除的图书！');
                return;
            }
            
            var bookIds = [];
            var bookTitles = [];
            
            selectedBooks.forEach(function(checkbox) {
                var row = checkbox.closest('tr');
                var bookId = checkbox.value;
                var bookTitle = row.cells[2].textContent;
                bookIds.push(bookId);
                bookTitles.push(bookTitle);
            });
            
            var confirmMessage = '确定要删除以下 ' + selectedBooks.length + ' 本图书吗？\n\n' + 
                                bookTitles.join('\n') + 
                                '\n\n此操作不可恢复！';
            
            if (confirm(confirmMessage)) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'batch_delete_books.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('批量删除成功！共删除 ' + selectedBooks.length + ' 本图书');
                            // 刷新页面
                            location.reload();
                        } else {
                            alert('批量删除失败：' + response.message);
                        }
                    }
                };
                
                xhr.send('book_ids=' + JSON.stringify(bookIds));
            }
        }


    </script>
</head>
<body>
    <!-- 左侧导航栏 -->
    <div class="sidebar">
        <div class="logo">图书管理后台管理</div>
        <ul>
            <li><a href="home.php"><i class="icon fa fa-home"></i>首页</a></li>
            <li><a href="site_setting.php"><i class="icon fa fa-cog"></i>网站设置</a></li>
            <li><a href="user_manage.php"><i class="icon fa fa-user"></i>用户管理</a></li>
            <!-- 新增图书管理相关导航 -->
            <li><a href="book_manage.php" class="active"><i class="icon fa fa-book"></i>图书管理</a></li>
            <li><a href="category_manage.php"><i class="icon fa fa-list"></i>分类管理</a></li>
            <li><a href="tag_manage.php"><i class="icon fa fa-tags"></i>标签管理</a></li>
            <!-- <li><a href="comment_manage.php"><i class="icon fa fa-comment"></i>评论管理</a></li> -->
            <li><a href="group_manage.php"><i class="icon fa fa-comments"></i>群聊管理</a></li>
        </ul>
    </div>

    <!-- 顶部栏 -->
    <div class="topbar">
        <div class="random-joke">图书管理</div>
        <div class="user-info">欢迎你，<?php echo $_SESSION['username']; ?>！<a href="logout.php">退出</a></div>
    </div>

    <!-- 主内容区 -->
    <div class="main">
        <div class="search-bar">
            <form method="get" action="book_manage.php" style="display: flex; gap: 10px; align-items: center;">
                <select name="category_id">
                    <option value="">全部分类</option>
                    <?php
                    $categorySql = "SELECT * FROM categories WHERE status = 1";
                    $categoryStmt = $pdo->query($categorySql);
                    $categories = $categoryStmt->fetchAll();
                    foreach ($categories as $category) {
                        $selected = (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : '';
                        echo '<option value="' . $category['id'] . '" ' . $selected . '>' . $category['name'] . '</option>';
                    }
                    ?>
                </select>
                <select name="status">
                    <option value="">全部状态</option>
                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>上架</option>
                    <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>下架</option>
                </select>
                <input type="text" name="title" placeholder="图书标题" value="<?php echo isset($_GET['title']) ? htmlspecialchars($_GET['title']) : ''; ?>">
                <input type="text" name="author" placeholder="作者" value="<?php echo isset($_GET['author']) ? htmlspecialchars($_GET['author']) : ''; ?>">
                <button type="submit">搜索</button>
                <?php if (isset($_GET['category_id']) && $_GET['category_id'] !== '' || isset($_GET['status']) && $_GET['status'] !== '' || isset($_GET['title']) && $_GET['title'] !== '' || isset($_GET['author']) && $_GET['author'] !== ''): ?>
                <a href="book_manage.php" class="reset-btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer;">重置</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="btn-group">
            <button class="add-btn" onclick="showAddBookModal()">+ 新增图书</button>
            <!-- <button class="edit-btn">修改</button> -->
            <button class="del-btn" onclick="batchDeleteBooks()">批量删除</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                    <th>ID</th>
                    <th>图书标题</th>
                    <th>作者</th>
                    <th>ISBN</th>
                    <th>出版社</th>
                    <th>分类</th>
                    <th>价格</th>
                    <th>库存</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book) : ?>
                <tr>
                    <td><input type="checkbox" name="book_ids" value="<?php echo $book['id']; ?>"></td>
                    <td><?php echo $book['id']; ?></td>
                    <td><?php echo $book['title']; ?></td>
                    <td><?php echo $book['author']; ?></td>
                    <td><?php echo isset($book['isbn']) ? $book['isbn'] : ''; ?></td>
                    <td><?php echo isset($book['publisher']) ? $book['publisher'] : ''; ?></td>
                    <td><?php echo isset($book['category_name']) ? $book['category_name'] : ''; ?></td>
                    <td>¥<?php echo $book['price']; ?></td>
                    <td><?php echo $book['stock']; ?></td>
                    <td>
                        <button class="status-switch <?php echo $book['status'] ? 'on' : ''; ?>" data-book-id="<?php echo $book['id']; ?>" data-status="<?php echo $book['status']; ?>"></button>
                    </td>
                    <td>
                        <a href="javascript:void(0);" onclick="showEditBookModal(<?php echo $book['id']; ?>)">修改</a>
                        <a href="javascript:void(0);" onclick="deleteBook(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">删除</a>
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

    <!-- 新增图书对话框 -->
    <div class="modal-overlay" id="addBookModal" style="display: none; z-index: 9999;">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">新增图书</h3>
                <button class="modal-close" onclick="hideAddBookModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addBookForm">
                    <div class="form-group">
                        <label for="title">图书标题</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="author">作者</label>
                        <input type="text" id="author" name="author" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="isbn">ISBN</label>
                            <input type="text" id="isbn" name="isbn">
                        </div>
                        <div class="form-group">
                            <label for="publisher">出版社</label>
                            <input type="text" id="publisher" name="publisher">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">分类</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">请选择分类</option>
                                <?php
                                $categorySql = "SELECT * FROM categories WHERE status = 1";
                                $categoryStmt = $pdo->query($categorySql);
                                $categories = $categoryStmt->fetchAll();
                                foreach ($categories as $category) {
                                    echo '<option value="' . $category['id'] . '">' . $category['name'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">状态</label>
                            <select id="status" name="status" required>
                                <option value="1">上架</option>
                                <option value="0">下架</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">价格</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="stock">库存</label>
                            <input type="number" id="stock" name="stock" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="hideAddBookModal()">取消</button>
                <button class="btn btn-confirm" onclick="submitAddBookForm()">确认</button>
            </div>
        </div>
    </div>

    <!-- 修改图书对话框 -->
    <div class="modal-overlay" id="editBookModal" style="display: none; z-index: 9999;">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">修改图书</h3>
                <button class="modal-close" onclick="hideEditBookModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editBookForm">
                    <input type="hidden" id="editBookId" name="book_id">
                    <div class="form-group">
                        <label for="editTitle">图书标题</label>
                        <input type="text" id="editTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="editAuthor">作者</label>
                        <input type="text" id="editAuthor" name="author" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editIsbn">ISBN</label>
                            <input type="text" id="editIsbn" name="isbn">
                        </div>
                        <div class="form-group">
                            <label for="editPublisher">出版社</label>
                            <input type="text" id="editPublisher" name="publisher">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editCategoryId">分类</label>
                            <select id="editCategoryId" name="category_id" required>
                                <option value="">请选择分类</option>
                                <?php
                                $categorySql = "SELECT * FROM categories WHERE status = 1";
                                $categoryStmt = $pdo->query($categorySql);
                                $categories = $categoryStmt->fetchAll();
                                foreach ($categories as $category) {
                                    echo '<option value="' . $category['id'] . '">' . $category['name'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editStatus">状态</label>
                            <select id="editStatus" name="status" required>
                                <option value="1">上架</option>
                                <option value="0">下架</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editPrice">价格</label>
                            <input type="number" id="editPrice" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editStock">库存</label>
                            <input type="number" id="editStock" name="stock" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="hideEditBookModal()">取消</button>
                <button class="btn btn-confirm" onclick="submitEditBookForm()">确认</button>
            </div>
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