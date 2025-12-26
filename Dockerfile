# 基础镜像（保持PHP 8.2版本，适配你的项目）
FROM php:8.2-apache

# 1. 启用mod_rewrite模块（解决RewriteEngine无效的核心步骤）
RUN a2enmod rewrite

# 2. 安装PHP必需扩展（mysqli/pdo_mysql，适配数据库连接）
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 3. 修改Apache主配置，允许.htaccess生效（关键：让重写指令被识别）
RUN sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 4. 复制项目文件到Apache根目录（最后复制，避免配置被覆盖）
COPY . /var/www/html/

# 5. 调整文件权限，确保Apache可读取.htaccess和项目文件
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# 6. 抑制Apache的"ServerName未定义"警告（日志中频繁出现的AH00558提示，可选但推荐）
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
