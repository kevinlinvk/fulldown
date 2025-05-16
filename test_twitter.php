<?php
/**
 * 测试Twitter视频下载插件
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 包含必要的文件
require_once 'includes/config.php';
require_once 'plugins/twitter_downloader/plugin_info.php';

// 创建插件实例
$plugin = new TwitterDownloaderPlugin();

// 测试URL
$url = 'https://x.com/Rainmaker1973/status/1922647761087869058';

// 处理URL
$result = $plugin->processUrl($url);

// 输出结果
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 