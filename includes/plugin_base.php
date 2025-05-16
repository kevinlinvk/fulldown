<?php
/**
 * 插件基类
 * 所有插件必须继承此类并实现其方法
 */
abstract class PluginBase {
    /**
     * 获取插件名称
     * @return string 插件名称
     */
    abstract public function getName();
    
    /**
     * 获取插件描述
     * @return string 插件描述
     */
    abstract public function getDescription();
    
    /**
     * 处理URL
     * @param string $url 用户输入的URL
     * @return array 处理结果，包含 success 状态和相关数据
     */
    abstract public function processUrl($url);
    
    /**
     * 检查插件是否支持给定的URL
     * @param string $url 要检查的URL
     * @return boolean 如果插件支持该URL则返回true
     */
    abstract public function supportsUrl($url);
} 