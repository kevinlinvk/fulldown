<?php
/**
 * 网站配置文件
 */

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 网站基本信息
define('SITE_NAME', '全能下载助手');
define('SITE_URL', 'http://localhost');

// 包含插件基类
require_once 'plugin_base.php';

// 创建API处理函数
function handleApiRequest() {
    // 增加最大执行时间和内存限制
    ini_set('max_execution_time', 300); // 5分钟
    ini_set('memory_limit', '256M');    // 256MB内存
    
    header('Content-Type: application/json');
    
    if (!isset($_POST['action'])) {
        echo json_encode(['success' => false, 'message' => '缺少必要参数']);
        exit;
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'process_url':
            if (!isset($_POST['url']) || !isset($_POST['plugin_id'])) {
                echo json_encode(['success' => false, 'message' => '缺少必要参数']);
                exit;
            }
            
            $url = $_POST['url'];
            $pluginId = $_POST['plugin_id'];
            
            // 记录请求信息到日志
            error_log("API请求: plugin_id={$pluginId}, url={$url}");
            
            try {
                // 加载插件处理器
                require_once 'plugin_loader.php';
                $pluginLoader = new PluginLoader();
                $plugins = $pluginLoader->loadPlugins();
                $plugin = $pluginLoader->getPlugin($pluginId);
                
                if (!$plugin) {
                    throw new Exception('插件不存在: ' . $pluginId);
                }
                
                if (!$plugin->supportsUrl($url)) {
                    throw new Exception('此插件不支持该URL');
                }
                
                $result = $plugin->processUrl($url);
                
                // 检查结果大小，如果太大则可能会导致JSON编码问题
                if (isset($result['data']['formats']) && count($result['data']['formats']) > 50) {
                    // 如果格式太多，只保留最佳的几个
                    $result['data']['formats'] = array_slice($result['data']['formats'], 0, 50);
                    $result['data']['note'] = '由于数据量太大，仅显示前50个格式';
                }
                
                // 记录结果状态
                $status = $result['success'] ? '成功' : '失败';
                error_log("API处理结果: {$status}");
                
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("API错误: " . $e->getMessage());
                echo json_encode([
                    'success' => false, 
                    'message' => $e->getMessage(),
                    'logs' => ['发生错误: ' . $e->getMessage()]
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '未知操作']);
            break;
    }
    
    exit;
} 