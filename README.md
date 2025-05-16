# 全能下载助手

基于PHP的插件式视频下载导航站点。通过灵活的插件系统，可轻松扩展支持更多视频平台。

## 特性

- 基于插件的架构，便于扩展
- 响应式设计，适配移动端和桌面端
- 简洁直观的用户界面
- 支持多种视频质量下载选择
- 详细的日志记录系统，便于调试和追踪

## 目前支持的平台

- 推特/Twitter/X 视频下载
- YouTube, 哔哩哔哩, TikTok等1000+平台 (通过多源下载器)

## 系统要求

- PHP 7.0 或更高版本
- PHP CURL, JSON, XML扩展
- Web服务器 (Apache/Nginx)
- FFmpeg（推荐，用于提高视频处理能力）

## 安装说明

1. 将所有文件上传至您的Web服务器
2. 确保 `plugins` 目录可写（如果需要动态安装插件）
3. 访问网站首页即可使用

## 插件开发

要添加新的下载服务，您只需在 `plugins` 目录中创建新的插件文件夹，并实现必要的接口：

1. 在 `plugins` 目录下创建新的插件文件夹，例如 `bilibili_downloader`
2. 创建 `plugin_info.php` 文件，包含插件信息和类定义引用
3. 创建插件主类文件，实现 `PluginBase` 抽象类的所有方法

参考示例：

```php
<?php
class BilibiliDownloaderPlugin extends PluginBase {
    public function getName() {
        return '哔哩哔哩视频下载';
    }
    
    public function getDescription() {
        return '下载哔哩哔哩平台视频';
    }
    
    public function supportsUrl($url) {
        return (strpos($url, 'bilibili.com') !== false);
    }
    
    public function processUrl($url) {
        // 实现处理逻辑...
    }
}
```

## 注意事项

- 本项目仅供学习研究使用
- 请遵守相关法律法规，不要下载受版权保护的内容
- 示例的Twitter下载插件为模拟实现，真实环境中需要调用相应API或爬虫技术

## 许可证

MIT 

## Linux服务器部署说明

对于Linux服务器，请按照以下步骤让程序正常工作：

### 1. 基本服务器配置

确保服务器安装以下软件包：

```bash
# Debian/Ubuntu系统
sudo apt update
sudo apt install php php-curl php-json php-xml php-mbstring ffmpeg

# CentOS/RHEL系统
sudo yum install epel-release
sudo yum install php php-curl php-json php-xml php-mbstring ffmpeg
```

### 2. 安装yt-dlp

在网站根目录创建`bin`文件夹并下载yt-dlp：

```bash
# 创建bin目录
mkdir -p /path/to/your/website/bin
cd /path/to/your/website/bin

# 下载yt-dlp
curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o yt-dlp
```

### 3. 文件权限设置

设置正确的文件权限：

```bash
# 设置yt-dlp可执行权限
chmod +x /path/to/your/website/bin/yt-dlp

# 确保web服务器用户有执行权限
# Apache通常使用www-data用户，Nginx可能使用nginx或www-data
chown www-data:www-data /path/to/your/website/bin/yt-dlp

# 设置日志文件权限
touch /path/to/your/website/download_log.txt
chmod 666 /path/to/your/website/download_log.txt
```

### 3.1 yt-dlp配置文件注意事项

如果您遇到yt-dlp配置文件编码问题，请确保配置文件使用UTF-8编码，或者采用以下方法之一：

1. 直接在代码中设置参数（推荐）：
   - 程序已设置为自动跳过配置文件，使用内置参数，无需额外配置

2. 如果您希望使用配置文件：
   ```bash
   # 确保配置文件使用UTF-8编码
   iconv -f GBK -t UTF-8 bin/yt-dlp.conf > bin/yt-dlp.conf.utf8
   mv bin/yt-dlp.conf.utf8 bin/yt-dlp.conf
   ```

3. 如果您在Linux环境下遇到"找不到yt-dlp"错误：
   ```bash
   # 检查yt-dlp是否可以独立运行
   cd /path/to/your/website
   ./bin/yt-dlp --version
   
   # 如果返回"permission denied"错误，设置执行权限
   chmod +x bin/yt-dlp
   ```

### 4. Web服务器配置

#### Apache配置示例

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your/website
    
    <Directory /path/to/your/website>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

#### Nginx配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/website;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # 根据您的PHP版本调整
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### 5. 安全注意事项

对于生产环境，强烈建议：

1. 使用HTTPS加密连接
2. 限制`bin`目录的直接访问
3. 设置合理的PHP执行超时时间
4. 考虑使用IP地理位置限制，避免滥用

### 6. 常见问题解决

1. **yt-dlp无法执行**：检查文件权限，确保可执行并且Web服务器用户有权限执行
   ```bash
   chmod +x /path/to/your/website/bin/yt-dlp
   chown www-data:www-data /path/to/your/website/bin/yt-dlp
   ```

2. **"未检测到可用的yt-dlp"错误**：确保yt-dlp路径正确，可以尝试使用绝对路径
   
3. **下载视频太慢或失败**：
   - 考虑配置代理：在bin/yt-dlp.conf中取消注释并配置代理
   ```
   --proxy 127.0.0.1:7890
   ```
   - 对于Twitter/X视频，可能需要配置cookies：
   ```
   --cookies-from-browser firefox:/path/to/your/firefox/profile
   ```
   - 在服务器上手动测试yt-dlp命令：
   ```bash
   cd /path/to/your/website
   ./bin/yt-dlp -f best "https://twitter.com/username/status/123456789"
   ```

4. **内存不足错误**：调整PHP内存限制
   ```
   memory_limit = 256M  # 在php.ini中设置
   ```

5. **多源下载器插件配置**：
   - 打开`plugins/multi_downloader/multi_downloader.php`
   - 找到`$config`数组
   - 根据需要配置代理、首选格式等选项
   ```php
   private $config = [
       'proxy' => 'http://127.0.0.1:7890',  // 配置代理
       'prefer_formats' => 'bestvideo+bestaudio/best', // 首选格式
       'prefer_ffmpeg' => true,  // 使用ffmpeg
       'log_to_file' => true,  // 记录日志到文件
   ];
   ```

系统会自动检测并使用网站目录下的yt-dlp程序，无需更改任何代码。 