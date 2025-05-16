# 全能下载助手

基于PHP的插件式视频下载导航站点。通过灵活的插件系统，可轻松扩展支持更多视频平台。

## 特性

- 基于插件的架构，便于扩展
- 响应式设计，适配移动端和桌面端
- 简洁直观的用户界面
- 支持多种视频质量下载选择

## 目前支持的平台

- 推特/Twitter/X 视频下载

## 系统要求

- PHP 7.0 或更高版本
- Web服务器 (Apache/Nginx)

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

对于Linux服务器，请按照以下步骤让Twitter下载插件正常工作：

1. 在网站根目录创建`bin`文件夹（如果不存在）
   ```bash
   mkdir -p /path/to/your/website/bin
   ```

2. 下载yt-dlp到bin目录
   ```bash
   cd /path/to/your/website/bin
   curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o yt-dlp
   ```

3. 赋予执行权限
   ```bash
   chmod +x /path/to/your/website/bin/yt-dlp
   ```

4. 确保web服务器用户（如www-data）有执行权限
   ```bash
   chown www-data:www-data /path/to/your/website/bin/yt-dlp
   ```

系统会自动检测并使用网站目录下的yt-dlp程序，无需更改任何代码。 