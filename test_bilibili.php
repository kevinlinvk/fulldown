<?php
/**
 * 测试哔哩哔哩视频下载插件
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5分钟
ini_set('memory_limit', '256M');    // 256MB内存

// 包含必要的文件
require_once 'includes/config.php';
require_once 'plugins/bilibili_downloader/plugin_info.php';

// 测试URL (可以通过URL参数传入)
$url = isset($_GET['url']) ? $_GET['url'] : 'https://www.bilibili.com/video/BV1GJ411x7h7';
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// 创建插件实例
$plugin = new BilibiliDownloaderPlugin();

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
    <title>哔哩哔哩下载器测试</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-entry { font-family: monospace; font-size: 12px; margin-bottom: 2px; }
        .json-viewer { white-space: pre-wrap; font-family: monospace; max-height: 300px; overflow-y: auto; font-size: 12px; }
        .dl-btn { margin-right: 5px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1>哔哩哔哩下载器测试</h1>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                测试信息
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="mb-3">
                        <label for="url" class="form-label">B站视频链接</label>
                        <input type="text" class="form-control" id="url" name="url" value="' . htmlspecialchars($url) . '">
                    </div>
                    <button type="submit" class="btn btn-primary">获取视频信息</button>
                    <a href="' . $_SERVER['PHP_SELF'] . '?url=' . urlencode($url) . '&format=json" class="btn btn-secondary">查看原始JSON</a>
                </form>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                处理结果
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
                    <div class="row">
                        <div class="col-md-8">
                            <h3>' . htmlspecialchars($result['data']['title'] ?? '未知标题') . '</h3>
                            <p><strong>UP主:</strong> ' . htmlspecialchars($result['data']['uploader'] ?? '未知') . '</p>
                            <p><strong>视频时长:</strong> ' . htmlspecialchars($result['data']['duration'] ?? '未知') . '</p>
                            <p><strong>上传日期:</strong> ' . htmlspecialchars($result['data']['upload_date'] ?? '未知') . '</p>
                            <p><strong>播放量:</strong> ' . number_format($result['data']['view_count'] ?? 0) . '</p>
                            <p><strong>点赞数:</strong> ' . number_format($result['data']['like_count'] ?? 0) . '</p>
                            <p><strong>BV号:</strong> ' . htmlspecialchars($result['data']['bvid'] ?? '未知') . '</p>
                        </div>';
                        
        if (!empty($result['data']['thumbnail'])) {
            echo '<div class="col-md-4 text-center">
                    <img src="' . htmlspecialchars($result['data']['thumbnail']) . '" class="img-fluid rounded" alt="缩略图">
                </div>';
        }
        
        echo '</div>';
                
        if (!empty($result['data']['description'])) {
            echo '<div class="mt-3">
                <h5>视频简介</h5>
                <div class="border p-2 rounded bg-light">
                    ' . nl2br(htmlspecialchars($result['data']['description'])) . '
                </div>
            </div>';
        }
                
        echo '</div>
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
                        <td>
                            <div class="btn-group" role="group">
                                <a href="' . htmlspecialchars($format['url']) . '" target="_blank" class="btn btn-sm btn-primary dl-btn">下载</a>
                                <button class="btn btn-sm btn-info dl-btn copy-link" data-url="' . htmlspecialchars($format['url']) . '">复制链接</button>
                            </div>
                        </td>
                    </tr>';
            }
            
            echo '</tbody>
                </table>
            </div>';
        }
    }
    
    echo '<script>
        // 复制链接功能
        document.addEventListener("DOMContentLoaded", function() {
            const copyButtons = document.querySelectorAll(".copy-link");
            copyButtons.forEach(button => {
                button.addEventListener("click", function() {
                    const url = this.getAttribute("data-url");
                    navigator.clipboard.writeText(url).then(() => {
                        const originalText = this.textContent;
                        this.textContent = "已复制!";
                        this.classList.remove("btn-info");
                        this.classList.add("btn-success");
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.classList.remove("btn-success");
                            this.classList.add("btn-info");
                        }, 2000);
                    }).catch(err => {
                        console.error("复制失败: ", err);
                        alert("复制链接失败");
                    });
                });
            });
        });
    </script>
    </div>
</body>
</html>';
} 