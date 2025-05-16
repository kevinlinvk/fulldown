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
            
            // 加载插件处理器
            require_once 'plugin_loader.php';
            $pluginLoader = new PluginLoader();
            $plugins = $pluginLoader->loadPlugins();
            $plugin = $pluginLoader->getPlugin($pluginId);
            
            if (!$plugin) {
                echo json_encode(['success' => false, 'message' => '插件不存在']);
                exit;
            }
            
            if (!$plugin->supportsUrl($url)) {
                echo json_encode(['success' => false, 'message' => '此插件不支持该URL']);
                exit;
            }
            
            $result = $plugin->processUrl($url);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '未知操作']);
            break;
    }
    
    exit;
} 