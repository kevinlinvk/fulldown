<?php
/**
 * 插件加载器类
 * 负责扫描插件目录，加载所有可用的插件
 */
class PluginLoader {
    private $pluginsDir = 'plugins';
    private $plugins = [];
    
    /**
     * 加载所有插件
     * @return array 已加载的插件列表
     */
    public function loadPlugins() {
        $this->scanPluginDirectory();
        return $this->plugins;
    }
    
    /**
     * 扫描插件目录
     */
    private function scanPluginDirectory() {
        if (!is_dir($this->pluginsDir)) {
            return;
        }
        
        $pluginFolders = glob($this->pluginsDir . '/*', GLOB_ONLYDIR);
        
        foreach ($pluginFolders as $pluginFolder) {
            $pluginInfoFile = $pluginFolder . '/plugin_info.php';
            
            if (file_exists($pluginInfoFile)) {
                include_once $pluginInfoFile;
                $folderName = basename($pluginFolder);
                $className = $this->getPluginClassName($folderName);
                
                if (class_exists($className)) {
                    $plugin = new $className();
                    $this->plugins[] = [
                        'id' => $folderName,
                        'name' => $plugin->getName(),
                        'description' => $plugin->getDescription(),
                        'instance' => $plugin
                    ];
                }
            }
        }
    }
    
    /**
     * 获取插件类名
     * @param string $folderName 插件文件夹名
     * @return string 插件类名
     */
    private function getPluginClassName($folderName) {
        // 转换文件夹名为类名: twitter_downloader => TwitterDownloader
        $parts = explode('_', $folderName);
        $className = '';
        
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        return $className . 'Plugin';
    }
    
    /**
     * 获取特定插件
     * @param string $pluginId 插件ID
     * @return object|null 插件实例
     */
    public function getPlugin($pluginId) {
        foreach ($this->plugins as $plugin) {
            if ($plugin['id'] === $pluginId) {
                return $plugin['instance'];
            }
        }
        return null;
    }
} 