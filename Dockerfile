FROM php:8.2-apache

# 1. 启用mod_rewrite模块（解决.htaccess的RewriteEngine错误）
RUN a2enmod rewrite

# 2. 修改Apache配置，允许.htaccess生效
RUN sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 3. 复制项目文件到Apache根目录（保持原有命令）
COPY . /var/www/html/

# 4. 安装PHP扩展（保持原有命令）
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 5. 调整文件权限（避免权限问题）
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
