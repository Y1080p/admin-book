-- 网站设置表
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '设置ID，自增主键',
  `setting_key` VARCHAR(50) NOT NULL COMMENT '设置键名',
  `setting_value` TEXT COMMENT '设置值',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '设置描述',
  `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站设置表';

-- 插入默认设置数据
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `description`) VALUES 
('site_name', '图书管理系统', '网站名称'),
('site_description', '专业的图书管理平台', '网站描述'),
('site_keywords', '图书,管理,系统', '网站关键词');