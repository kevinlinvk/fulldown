<?php
/**
 * shell_exec函数测试脚本
 * 用于验证PHP是否能正确执行外部命令
 */

// 打印PHP版本和运行环境
echo "PHP版本: " . PHP_VERSION . "\n";
echo "运行环境: " . php_sapi_name() . "\n";
echo "操作系统: " . PHP_OS . "\n";
echo "当前工作目录: " . getcwd() . "\n\n";

// 测试基本命令执行
echo "测试基本命令...\n";
if (DIRECTORY_SEPARATOR === '/') {
    // Linux/Mac 命令
    echo "执行: ls -la\n";
    echo shell_exec('ls -la 2>&1');
} else {
    // Windows 命令
    echo "执行: dir\n";
    echo shell_exec('dir 2>&1');
}
echo "\n";

// 测试yt-dlp执行
echo "测试yt-dlp...\n";
$binPaths = [
    __DIR__ . '/bin/yt-dlp',
    __DIR__ . '/bin/yt-dlp.exe',
    'yt-dlp',
    'yt-dlp.exe'
];

$ytDlpFound = false;
foreach ($binPaths as $bin) {
    echo "尝试: $bin\n";
    $cmd = escapeshellarg($bin) . ' --version 2>&1';
    $output = shell_exec($cmd);
    
    if ($output && stripos($output, 'error') === false) {
        echo "成功: $bin 版本 - " . trim($output) . "\n";
        $ytDlpFound = true;
        break;
    } else {
        echo "失败: $bin - " . ($output ? trim($output) : '无输出') . "\n";
    }
}

if (!$ytDlpFound) {
    echo "警告: 未找到可用的yt-dlp\n";
}

// 检查函数禁用情况
echo "\n检查函数禁用情况...\n";
$disabledFunctions = explode(',', ini_get('disable_functions'));
$relevantFunctions = ['shell_exec', 'exec', 'system', 'passthru', 'proc_open'];

foreach ($relevantFunctions as $func) {
    if (in_array($func, $disabledFunctions) || !function_exists($func)) {
        echo "$func: 已禁用\n";
    } else {
        echo "$func: 可用\n";
    }
}

echo "\n测试完成\n";
?>