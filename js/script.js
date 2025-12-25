// 图书管理系统前端JavaScript
class BookManager {
    constructor() {
        this.books = [];
        this.categories = [];
        this.currentSearch = '';
        this.currentCategory = '';
        this.apiUrl = 'php/api.php';
        
        this.init();
    }
    
    // 初始化
    async init() {
        this.bindEvents();
        await this.loadStats();
        await this.loadCategories();
        await this.loadBooks();
        this.hideLoading();
    }
    
    // 绑定事件
    bindEvents() {
        // 搜索功能
        document.getElementById('search-input').addEventListener('input', (e) => {
            this.currentSearch = e.target.value;
            this.debounce(() => this.loadBooks(), 300)();
        });
        
        // 分类筛选
        document.getElementById('category-filter').addEventListener('change', (e) => {
            this.currentCategory = e.target.value;
            this.loadBooks();
        });
        
        // 刷新按钮
        document.getElementById('refresh-btn').addEventListener('click', () => {
            this.refreshAll();
        });
        
        // 添加图书表单
        document.getElementById('add-book-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addBook();
        });
        
        // 保存编辑
        document.getElementById('save-edit-btn').addEventListener('click', () => {
            this.updateBook();
        });
        
        // 导航链接平滑滚动
        document.querySelectorAll('a.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    // 防抖函数
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // 显示加载动画
    showLoading() {
        document.getElementById('loading').classList.add('show');
    }
    
    // 隐藏加载动画
    hideLoading() {
        document.getElementById('loading').classList.remove('show');
    }
    
    // 显示消息提示
    showMessage(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert ${alertClass} alert-dismissible fade show`;
        messageDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // 插入到页面顶部
        const container = document.querySelector('.container');
        container.insertBefore(messageDiv, container.firstChild);
        
        // 自动消失
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
    
    // API请求封装
    async apiCall(action, method = 'GET', data = null) {
        try {
            this.showLoading();
            
            const url = new URL(this.apiUrl, window.location.origin);
            url.searchParams.set('action', action);
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };
            
            if (data && (method === 'POST' || method === 'PUT')) {
                options.body = JSON.stringify(data);
            }
            
            if (method === 'DELETE' && data) {
                url.searchParams.set('id', data);
            }
            
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            return result;
            
        } catch (error) {
            console.error('API调用失败:', error);
            this.showMessage(error.message || '操作失败，请重试', 'danger');
            throw error;
        } finally {
            this.hideLoading();
        }
    }
    
    // 加载统计信息
    async loadStats() {
        try {
            const result = await this.apiCall('get_stats');
            this.updateStats(result.data);
        } catch (error) {
            console.error('加载统计信息失败:', error);
        }
    }
    
    // 更新统计信息显示
    updateStats(stats) {
        document.getElementById('total-books').textContent = stats.total_books;
        document.getElementById('total-authors').textContent = stats.total_authors;
        document.getElementById('total-categories').textContent = stats.total_categories;
        
        // 添加数字动画效果
        this.animateNumbers();
    }
    
    // 数字动画效果
    animateNumbers() {
        const counters = document.querySelectorAll('.stat-card h3');
        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            let current = 0;
            const increment = target / 50;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.ceil(current);
            }, 30);
        });
    }
    
    // 加载分类列表
    async loadCategories() {
        try {
            const result = await this.apiCall('get_categories');
            this.categories = result.data;
            this.renderCategories();
        } catch (error) {
            console.error('加载分类列表失败:', error);
        }
    }
    
    // 渲染分类下拉框
    renderCategories() {
        const select = document.getElementById('category-filter');
        select.innerHTML = '<option value="">所有分类</option>';
        
        this.categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            select.appendChild(option);
        });
    }
    
    // 加载图书列表
    async loadBooks() {
        try {
            const params = {};
            if (this.currentSearch) params.search = this.currentSearch;
            if (this.currentCategory) params.category = this.currentCategory;
            
            const urlParams = new URLSearchParams(params);
            const result = await this.apiCall('get_books?' + urlParams.toString());
            this.books = result.data;
            this.renderBooks();
        } catch (error) {
            console.error('加载图书列表失败:', error);
        }
    }
    
    // 渲染图书列表
    renderBooks() {
        const container = document.getElementById('books-container');
        
        if (this.books.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">暂无图书数据</h4>
                    <p class="text-muted">点击"添加图书"来添加第一本图书</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        
        this.books.forEach((book, index) => {
            const bookCard = this.createBookCard(book, index);
            container.appendChild(bookCard);
        });
    }
    
    // 创建图书卡片
    createBookCard(book, index) {
        const col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4';
        
        col.innerHTML = `
            <div class="card book-card animate-in" style="animation-delay: ${index * 0.1}s">
                <div class="card-header">
                    <h5 class="card-title mb-0">${this.escapeHtml(book.title)}</h5>
                </div>
                <div class="card-body">
                    <div class="book-info">
                        <div class="info-item">
                            <span class="info-label">作者:</span>
                            <span class="info-value">${this.escapeHtml(book.author)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">出版年份:</span>
                            <span class="info-value">${book.publish_year}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">分类:</span>
                            <span class="info-value">${this.escapeHtml(book.category)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">价格:</span>
                            <span class="price-tag">¥${book.price}</span>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning btn-sm" onclick="bookManager.editBook(${book.id})">
                            <i class="fas fa-edit me-1"></i>编辑
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="bookManager.deleteBook(${book.id})">
                            <i class="fas fa-trash me-1"></i>删除
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        return col;
    }
    
    // 转义HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 添加图书
    async addBook() {
        const form = document.getElementById('add-book-form');
        const formData = new FormData(form);
        
        const bookData = {
            title: formData.get('title') || document.getElementById('title').value,
            author: formData.get('author') || document.getElementById('author').value,
            publish_year: parseInt(formData.get('publish_year') || document.getElementById('publish_year').value),
            price: parseFloat(formData.get('price') || document.getElementById('price').value),
            category: formData.get('category') || document.getElementById('category').value
        };
        
        try {
            const result = await this.apiCall('add_book', 'POST', bookData);
            this.showMessage('图书添加成功！', 'success');
            form.reset();
            await this.refreshAll();
        } catch (error) {
            // 错误信息已经在apiCall中显示
        }
    }
    
    // 编辑图书
    async editBook(bookId) {
        const book = this.books.find(b => b.id === bookId);
        if (!book) return;
        
        // 填充表单
        document.getElementById('edit-id').value = book.id;
        document.getElementById('edit-title').value = book.title;
        document.getElementById('edit-author').value = book.author;
        document.getElementById('edit-publish_year').value = book.publish_year;
        document.getElementById('edit-price').value = book.price;
        document.getElementById('edit-category').value = book.category;
        
        // 显示模态框
        const modal = new bootstrap.Modal(document.getElementById('editBookModal'));
        modal.show();
    }
    
    // 更新图书
    async updateBook() {
        const bookData = {
            id: parseInt(document.getElementById('edit-id').value),
            title: document.getElementById('edit-title').value,
            author: document.getElementById('edit-author').value,
            publish_year: parseInt(document.getElementById('edit-publish_year').value),
            price: parseFloat(document.getElementById('edit-price').value),
            category: document.getElementById('edit-category').value
        };
        
        try {
            const result = await this.apiCall('update_book', 'PUT', bookData);
            this.showMessage('图书信息更新成功！', 'success');
            
            // 关闭模态框
            const modal = bootstrap.Modal.getInstance(document.getElementById('editBookModal'));
            modal.hide();
            
            await this.refreshAll();
        } catch (error) {
            // 错误信息已经在apiCall中显示
        }
    }
    
    // 删除图书
    async deleteBook(bookId) {
        if (!confirm('确定要删除这本图书吗？此操作不可撤销。')) {
            return;
        }
        
        try {
            // 添加删除动画
            const bookCard = document.querySelector(`[onclick*="bookManager.deleteBook(${bookId})"]`).closest('.book-card');
            if (bookCard) {
                bookCard.classList.add('deleting');
            }
            
            await this.apiCall('delete_book', 'DELETE', bookId);
            this.showMessage('图书删除成功！', 'success');
            await this.refreshAll();
        } catch (error) {
            // 错误信息已经在apiCall中显示
        }
    }
    
    // 刷新所有数据
    async refreshAll() {
        await Promise.all([
            this.loadStats(),
            this.loadCategories(),
            this.loadBooks()
        ]);
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    window.bookManager = new BookManager();
});

// 添加一些额外的交互效果
document.addEventListener('DOMContentLoaded', () => {
    // 卡片悬停效果增强
    document.addEventListener('mouseover', (e) => {
        if (e.target.closest('.book-card')) {
            const card = e.target.closest('.book-card');
            card.style.transform = 'translateY(-5px) scale(1.02)';
        }
    });
    
    document.addEventListener('mouseout', (e) => {
        if (e.target.closest('.book-card')) {
            const card = e.target.closest('.book-card');
            card.style.transform = '';
        }
    });
    
    // 表单输入动画
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', () => {
            if (!input.value) {
                input.parentElement.classList.remove('focused');
            }
        });
    });
});