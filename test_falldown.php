<?php
/**
 * 测试Fall down全站视频下载插件
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5分钟
ini_set('memory_limit', '256M');    // 256MB内存

// 包含必要的文件
require_once 'includes/config.php';
require_once 'plugins/falldown_downloader/plugin_info.php';

// 测试URL
$url = isset($_GET['url']) ? $_GET['url'] : 'https://x.com/Rainmaker1973/status/1922647761087869058';
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// 创建插件实例
$plugin = new FalldownDownloaderPlugin();

// 开始计时
$start_time = microtime(true);

// 处理URL
$result = $plugin->processUrl($url);

// 结束计时
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

// 输出结果
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    // HTML格式输出
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falldown插件测试</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-entry { font-family: monospace; }
        .json-viewer { white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1>Falldown插件测试</h1>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                测试信息
            </div>
            <div class="card-body">
                <p><strong>测试URL:</strong> ' . htmlspecialchars($url) . '</p>
                <p><strong>执行时间:</strong> ' . $execution_time . ' 秒</p>
                <p><strong>处理结果:</strong> ' . ($result['success'] ? '<span class="text-success">成功</span>' : '<span class="text-danger">失败</span>') . '</p>
            </div>
        </div>';
        
    if (!$result['success']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($result['message']) . '</div>';
    }
    
    echo '<h2>日志输出</h2>
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                执行日志
            </div>
            <div class="card-body">
                <div class="logs-container">';
    
    if (isset($result['logs']) && is_array($result['logs'])) {
        foreach ($result['logs'] as $log) {
            echo '<div class="log-entry">' . htmlspecialchars($log) . '</div>';
        }
    } else {
        echo '<div class="alert alert-warning">无日志输出</div>';
    }
    
    echo '</div>
            </div>
        </div>';
    
    if ($result['success'] && isset($result['data'])) {
        echo '<h2>视频信息</h2>
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    基本信息
                </div>
                <div class="card-body">
                    <p><strong>标题:</strong> ' . htmlspecialchars($result['data']['title'] ?? '未知') . '</p>
                    <p><strong>上传者:</strong> ' . htmlspecialchars($result['data']['uploader'] ?? '未知') . '</p>
                    <p><strong>时长:</strong> ' . htmlspecialchars($result['data']['duration'] ?? '未知') . '</p>
                    <p><strong>上传日期:</strong> ' . htmlspecialchars($result['data']['upload_date'] ?? '未知') . '</p>
                </div>
            </div>';
        
        if (isset($result['data']['formats']) && is_array($result['data']['formats'])) {
            echo '<h2>可用下载格式 (' . count($result['data']['formats']) . ')</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>质量</th>
                                <th>格式</th>
                                <th>大小</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            foreach ($result['data']['formats'] as $format) {
                echo '<tr>
                        <td>' . htmlspecialchars($format['quality'] ?? '未知') . '</td>
                        <td>' . htmlspecialchars($format['ext'] ?? '未知') . '</td>
                        <td>' . htmlspecialchars($format['size'] ?? '未知') . '</td>
                        <td><a href="' . htmlspecialchars($format['url']) . '" target="_blank" class="btn btn-sm btn-primary">下载</a></td>
                    </tr>';
            }
            
            echo '</tbody>
                </table>
            </div>';
        }
        
        echo '<h2>完整JSON数据</h2>
            <div class="card">
                <div class="card-header bg-info text-white">
                    JSON
                </div>
                <div class="card-body">
                    <div class="json-viewer">' . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</div>
                </div>
            </div>';
    }
    
    echo '</div>
</body>
</html>';
} 