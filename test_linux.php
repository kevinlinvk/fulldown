<?php
/**
 * Linux环境下yt-dlp测试脚本
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 判断操作系统
$isWindows = (DIRECTORY_SEPARATOR === '\\');
$isLinux = (DIRECTORY_SEPARATOR === '/');

echo "系统环境检测：\n";
echo "操作系统: " . PHP_OS . "\n";
echo "分隔符: " . DIRECTORY_SEPARATOR . "\n";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "执行模式: " . php_sapi_name() . "\n\n";

// 检测yt-dlp是否存在
$binPaths = [
    __DIR__ . '/bin/yt-dlp',
    __DIR__ . '/bin/yt-dlp.exe',
];

$ytDlpBin = null;
foreach ($binPaths as $bin) {
    if (file_exists($bin)) {
        echo "找到yt-dlp: {$bin}\n";
        
        // 检查文件权限
        if ($isLinux) {
            $perms = fileperms($bin);
            $isExecutable = ($perms & 0x0040); // 检查用户可执行权限
            echo "文件权限: " . substr(sprintf('%o', $perms), -4) . "\n";
            echo "可执行: " . ($isExecutable ? "是" : "否") . "\n";
            
            if (!$isExecutable) {
                echo "尝试设置可执行权限...\n";
                chmod($bin, 0755);
                echo "权限已更新为: " . substr(sprintf('%o', fileperms($bin)), -4) . "\n";
            }
        }
        
        $ytDlpBin = $bin;
        break;
    }
}

if (!$ytDlpBin) {
    echo "在bin目录未找到yt-dlp，尝试在系统PATH中查找\n";
    
    // 在系统PATH中查找
    $testCmd = $isWindows ? 'where yt-dlp' : 'which yt-dlp';
    $output = shell_exec($testCmd);
    
    if ($output) {
        $ytDlpBin = trim($output);
        echo "在系统PATH中找到yt-dlp: {$ytDlpBin}\n";
    } else {
        die("未找到yt-dlp，请确保已安装或放置在正确位置\n");
    }
}

// 测试yt-dlp版本
echo "\n测试yt-dlp版本：\n";
$cmd = escapeshellarg($ytDlpBin) . ' --version';
$version = shell_exec($cmd);
echo "yt-dlp版本: " . trim($version) . "\n\n";

// 测试Twitter URL
$twitterUrl = 'https://x.com/Rainmaker1973/status/1922647761087869058';
echo "测试下载Twitter视频: {$twitterUrl}\n";

// 设置临时配置位置
$configNull = $isLinux ? '/dev/null' : 'NUL';

// 构建命令
$cmd = escapeshellarg($ytDlpBin) . ' --config-location ' . $configNull . ' -j --no-warnings --no-playlist --force-ipv4 ' . escapeshellarg($twitterUrl);
echo "执行命令: {$cmd}\n";

// 执行命令
$startTime = microtime(true);
$output = shell_exec($cmd . " 2>&1");
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "命令执行时间: {$executionTime} 秒\n\n";

// 检查输出
if (!$output) {
    echo "错误: 命令执行失败或未返回任何输出\n";
    exit(1);
}

// 检查是否存在错误信息
if (stripos($output, 'error') !== false) {
    echo "错误: yt-dlp返回错误\n";
    echo "错误信息: " . substr($output, 0, 500) . "\n";
    exit(1);
}

// 尝试解析JSON
$json = json_decode($output, true);
if (!$json) {
    echo "错误: 无法解析yt-dlp的JSON输出\n";
    echo "原始输出: " . substr($output, 0, 500) . "\n";
    exit(1);
}

// 输出视频信息摘要
echo "视频信息获取成功！\n";
echo "标题: " . ($json['title'] ?? '未知') . "\n";
echo "ID: " . ($json['id'] ?? '未知') . "\n";
echo "缩略图URL: " . ($json['thumbnail'] ?? '未知') . "\n";
echo "上传者: " . ($json['uploader'] ?? '未知') . "\n";
echo "可用格式数: " . count($json['formats'] ?? []) . "\n";

// 测试成功
echo "\n测试完成: 成功\n";
exit(0); 