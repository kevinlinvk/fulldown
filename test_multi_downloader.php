<?php
/**
 * 多源下载器测试文件
 */

// 包含必要的文件
require_once 'includes/config.php';
require_once 'includes/plugin_base.php';
require_once 'includes/plugin_loader.php';
require_once 'plugins/multi_downloader/plugin_info.php';

// 测试URL
$testUrl = "https://twitter.com/Rainmaker1973/status/1922647761087869058";

// 创建插件实例
$plugin = new MultiDownloaderPlugin();

// 输出详细信息
echo "测试URL: {$testUrl}\n";
echo "插件名称: " . $plugin->getName() . "\n";
echo "插件描述: " . $plugin->getDescription() . "\n";
echo "是否支持该URL: " . ($plugin->supportsUrl($testUrl) ? '是' : '否') . "\n";

// 处理URL
echo "\n开始处理URL...\n";
$result = $plugin->processUrl($testUrl);

// 输出结果
echo "\n处理结果: " . ($result['success'] ? '成功' : '失败') . "\n";
if (!$result['success']) {
    echo "错误消息: " . $result['message'] . "\n";
}

// 输出日志
echo "\n详细日志:\n";
foreach ($result['logs'] as $log) {
    if (is_array($log)) {
        echo $log['text'] . "\n";
    } else {
        echo $log . "\n";
    }
}

// 如果成功，输出视频信息
if ($result['success']) {
    $data = $result['data'];
    echo "\n视频信息:\n";
    echo "标题: " . $data['title'] . "\n";
    echo "上传者: " . $data['uploader'] . "\n";
    echo "持续时间: " . $data['duration'] . "\n";
    echo "格式数量: " . count($data['formats']) . "\n";
    
    // 输出第一个格式信息
    if (!empty($data['formats'])) {
        $format = $data['formats'][0];
        echo "\n第一个格式信息:\n";
        echo "质量: " . $format['quality'] . "\n";
        echo "格式: " . ($format['ext'] ?? '未知') . "\n";
        echo "大小: " . $format['size'] . "\n";
        echo "URL: " . substr($format['url'], 0, 100) . "...\n";
    }
} 