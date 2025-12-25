-- 图书管理系统数据库表结构

-- 用户信息表
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID，自增主键',
  `username` VARCHAR(50) NOT NULL COMMENT '用户账号，唯一',
  `password` VARCHAR(50) NOT NULL COMMENT '用户密码（明文）',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `email` VARCHAR(50) DEFAULT NULL COMMENT '邮箱',
  `status` TINYINT(1) DEFAULT 1 COMMENT '用户状态（1启用，0禁用）',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像地址',
  `gender` TINYINT(1) DEFAULT 0 COMMENT '性别（0未知，1男，2女）',
  `intro` VARCHAR(255) DEFAULT NULL COMMENT '个人简介',
  `role` VARCHAR(20) DEFAULT '员工' COMMENT '角色（admin为管理员，员工为员工，用户为普通用户）',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique` (`username`),
  KEY `phone_index` (`phone`),
  KEY `email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户信息表';

-- 图书分类表
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID，自增主键',
  `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '分类描述',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态（1启用，0禁用）',
  `sort_order` INT(11) DEFAULT 0 COMMENT '排序',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图书分类表';

-- 图书标签表
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '标签ID，自增主键',
  `name` VARCHAR(50) NOT NULL COMMENT '标签名称',
  `color` VARCHAR(20) DEFAULT '#409eff' COMMENT '标签颜色',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态（1启用，0禁用）',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图书标签表';

-- 图书信息表
CREATE TABLE IF NOT EXISTS `books` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '图书ID，自增主键',
  `title` VARCHAR(100) NOT NULL COMMENT '图书标题',
  `author` VARCHAR(50) NOT NULL COMMENT '作者',
  `isbn` VARCHAR(20) DEFAULT NULL COMMENT 'ISBN号',
  `publisher` VARCHAR(100) DEFAULT NULL COMMENT '出版社',
  `publish_date` DATE DEFAULT NULL COMMENT '出版日期',
  `category_id` INT(11) NOT NULL COMMENT '分类ID',
  `cover_image` VARCHAR(255) DEFAULT NULL COMMENT '封面图片',
  `description` TEXT COMMENT '图书描述',
  `price` DECIMAL(10,2) DEFAULT 0.00 COMMENT '价格',
  `stock` INT(11) DEFAULT 0 COMMENT '库存数量',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态（1上架，0下架）',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `category_id_index` (`category_id`),
  KEY `author_index` (`author`),
  KEY `title_index` (`title`),
  CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图书信息表';

-- 图书标签关联表
CREATE TABLE IF NOT EXISTS `book_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID，自增主键',
  `book_id` INT(11) NOT NULL COMMENT '图书ID',
  `tag_id` INT(11) NOT NULL COMMENT '标签ID',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_tag_unique` (`book_id`, `tag_id`),
  KEY `book_id_index` (`book_id`),
  KEY `tag_id_index` (`tag_id`),
  CONSTRAINT `fk_book_tags_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图书标签关联表';

-- 图书评论表
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '评论ID，自增主键',
  `book_id` INT(11) NOT NULL COMMENT '图书ID',
  `user_id` INT(11) NOT NULL COMMENT '用户ID',
  `content` TEXT NOT NULL COMMENT '评论内容',
  `rating` TINYINT(1) DEFAULT 5 COMMENT '评分（1-5）',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态（1显示，0隐藏）',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `book_id_index` (`book_id`),
  KEY `user_id_index` (`user_id`),
  -- 新增外键约束，关联 books 表和 users 表
  CONSTRAINT `fk_comments_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图书评论表';

-- 群聊表
CREATE TABLE IF NOT EXISTS `chat_groups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '群聊ID，自增主键',
  `group_name` VARCHAR(100) NOT NULL COMMENT '群聊名称',
  `group_owner_id` INT(11) NOT NULL COMMENT '群主ID',
  `description` TEXT COMMENT '群聊描述',
  `max_members` INT(11) DEFAULT 200 COMMENT '最大成员数',
  `current_members` INT(11) DEFAULT 0 COMMENT '当前成员数',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态（1正常，0关闭）',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `group_owner_index` (`group_owner_id`),
  CONSTRAINT `fk_chat_groups_owner` FOREIGN KEY (`group_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='群聊表';

-- 群聊消息表
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '消息ID，自增主键',
  `group_id` INT(11) NOT NULL COMMENT '群聊ID',
  `user_id` INT(11) NOT NULL COMMENT '发送用户ID',
  `content` TEXT NOT NULL COMMENT '消息内容',
  `message_type` TINYINT(1) DEFAULT 1 COMMENT '消息类型（1文本，2图片，3文件）',
  `file_path` VARCHAR(255) DEFAULT NULL COMMENT '文件路径',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态（1正常，0删除）',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `group_id_index` (`group_id`),
  KEY `user_id_index` (`user_id`),
  CONSTRAINT `fk_chat_messages_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='群聊消息表';

-- 插入测试数据

-- 用户测试数据
INSERT INTO `users` (`username`, `password`, `phone`, `email`, `status`, `avatar`, `gender`, `intro`, `role`) 
VALUES 
('YJM', 'yjm123', '', '', 1, 'images/avatar1.jpg', 1, '', '普通用户'),
('123', '123456', '', '', 1, 'images/avatar2.jpg', 0, '', '普通用户'),
('sleepduck', 'sleep123', '', '', 1, 'images/avatar3.jpg', 0, '', '普通用户'),
('admin', 'admin123', '', 'admin@example.com', 1, 'images/avatar4.jpg', 0, '系统管理员', 'admin');

-- 分类测试数据
INSERT INTO `categories` (`name`, `description`, `sort_order`) 
VALUES 
('文学小说', '各类文学作品和小说', 1),
('科学技术', '科技、计算机、工程类书籍', 2),
('历史传记', '历史事件和人物传记', 3),
('经济管理', '经济学和管理学书籍', 4),
('生活艺术', '生活、艺术、设计类书籍', 5);

-- 标签测试数据
INSERT INTO `tags` (`name`, `color`) 
VALUES 
('热门', '#f56c6c'),
('新书', '#67c23a'),
('经典', '#e6a23c'),
('推荐', '#409eff'),
('限时优惠', '#f56c6c');

-- 图书测试数据
INSERT INTO `books` (`title`, `author`, `isbn`, `publisher`, `publish_date`, `category_id`, `cover_image`, `description`, `price`, `stock`) 
VALUES 
('三体', '刘慈欣', '9787536692930', '重庆出版社', '2008-01-01', 1, 'images/book1.jpg', '科幻小说经典之作', 45.00, 100),
('JavaScript高级程序设计', 'Nicholas C. Zakas', '9787115275790', '人民邮电出版社', '2012-03-01', 2, 'images/book2.jpg', '前端开发必读经典', 89.00, 50),
('人类简史', '尤瓦尔·赫拉利', '9787508647357', '中信出版社', '2014-11-01', 3, 'images/book3.jpg', '从动物到上帝的人类历史', 68.00, 80),
('经济学原理', 'N.格里高利·曼昆', '9787301256911', '北京大学出版社', '2015-05-01', 4, 'images/book4.jpg', '经济学入门经典教材', 88.00, 60),
('设计中的设计', '原研哉', '9787530946197', '山东人民出版社', '2006-11-01', 5, 'images/book5.jpg', '设计思维与美学', 48.00, 40);

-- 图书标签关联测试数据
INSERT INTO `book_tags` (`book_id`, `tag_id`) 
VALUES 
(1, 1), (1, 3), (1, 4),
(2, 1), (2, 2), (2, 4),
(3, 1), (3, 3), (3, 4),
(4, 3), (4, 4),
(5, 3), (5, 4);

-- 评论测试数据
INSERT INTO `comments` (`book_id`, `user_id`, `content`, `rating`) 
VALUES 
(1, 1, '非常精彩的科幻小说，想象力丰富！', 5),
(1, 2, '读了三遍，每次都有新收获', 5),
(2, 3, '前端开发必备，内容详实', 4),
(3, 1, '视角独特，对人类历史的全新解读', 5),
(4, 4, '经济学入门的好书，通俗易懂', 4);

-- 群聊测试数据
INSERT INTO `chat_groups` (`group_name`, `group_owner_id`, `description`, `max_members`, `current_members`, `status`) 
VALUES 
('文学爱好者交流群', 1, '文学小说爱好者交流群，分享阅读心得和好书推荐', 200, 45, 1),
('科技前沿讨论群', 2, '科技、计算机、编程技术交流群', 100, 32, 1),
('历史研究小组', 3, '历史事件和人物研究讨论群', 50, 18, 1),
('经济学学习群', 4, '经济学原理和实践学习交流群', 150, 67, 1),
('艺术设计交流群', 1, '生活艺术、设计创意交流群', 80, 25, 0);

-- 群聊消息测试数据
INSERT INTO `chat_messages` (`group_id`, `user_id`, `content`) 
VALUES 
(1, 1, '大家好，今天有什么好书推荐吗？'),
(1, 2, '最近在读《三体》，真的很不错'),
(2, 3, '有没有前端开发相关的书籍推荐？'),
(3, 4, '《经济学原理》这本书很适合入门');