<?php
/**
 * Fall down全站视频下载器
 * 支持通过yt-dlp下载超过1000+网站的视频
 */
class FalldownDownloaderPlugin extends PluginBase {
    /**
     * 日志缓存
     */
    private array $logs = [];

    /**
     * 配置选项
     */
    private array $config = [
        // 是否使用代理
        'use_proxy' => false,
        // 代理地址
        'proxy' => '',
        // 视频格式优先级
        'format_priority' => 'bestvideo+bestaudio/best',
        // 特定站点的处理规则
        'site_rules' => [
            'twitter.com' => [
                'special_args' => '--force-ipv4 --extractor-args "twitter:tweet_mode=extended"',
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
                    'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                    'DNT' => '1'
                ]
            ],
            'youtube.com' => [
                'special_args' => '--format "bestvideo+bestaudio/best"',
                'headers' => []
            ],
            'bilibili.com' => [
                'special_args' => '--format "best"',
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.bilibili.com/'
                ]
            ]
        ]
    ];

    /**
     * 获取插件名称
     */
    public function getName(): string {
        return 'Fall down全站视频下载器';
    }

    /**
     * 获取插件描述
     */
    public function getDescription(): string {
        return '支持超过1000+网站的视频下载，包括YouTube、哔哩哔哩、推特等';
    }

    /**
     * 检查URL是否支持
     * 本插件支持大多数视频网站
     */
    public function supportsUrl($url) {
        // 支持几乎所有视频网站
        return true;
    }

    /**
     * 处理URL，核心方法
     */
    public function processUrl($url) {
        $this->logs = [];
        $this->log("原始 URL: {$url}");

        $url = $this->cleanUrl($url);
        $this->log("清理后 URL: {$url}");

        try {
            // 检测URL类型
            $siteType = $this->detectSiteType($url);
            $this->log("检测到网站类型: {$siteType}");

            // 获取视频信息
            $videoInfo = $this->fetchVideoInfo($url, $siteType);
            
            if (empty($videoInfo['formats'])) {
                throw new \RuntimeException('未能获取任何视频格式');
            }
            
            $this->log("成功获取视频信息: " . ($videoInfo['title'] ?? '未知标题'));

            return [
                'success' => true,
                'data'    => $videoInfo,
                'logs'    => $this->logs
            ];
        } catch (\Exception $e) {
            $this->log('错误: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'logs'    => $this->logs
            ];
        }
    }

    /**
     * 使用yt-dlp获取视频信息
     */
    private function fetchVideoInfo($url, $siteType = 'generic') {
        // 查找yt-dlp可执行文件
        $ytDlpBin = $this->findYtDlpExecutable();
        $this->log("使用解析器: {$ytDlpBin}");

        // 构建基本参数
        $baseParams = ' -j --no-warnings --no-playlist';
        
        // 特定站点的额外参数
        $extraParams = '';
        if (isset($this->config['site_rules'][$siteType])) {
            $rule = $this->config['site_rules'][$siteType];
            
            // 添加特殊参数
            if (!empty($rule['special_args'])) {
                $extraParams .= ' ' . $rule['special_args'];
            }
            
            // 添加HTTP头
            foreach ($rule['headers'] as $key => $value) {
                $extraParams .= ' --add-header "' . $key . ':' . $value . '"';
            }
        }
        
        // 添加代理设置
        if ($this->config['use_proxy'] && !empty($this->config['proxy'])) {
            $extraParams .= ' --proxy ' . escapeshellarg($this->config['proxy']);
            $this->log("使用代理: " . $this->config['proxy']);
        }
        
        // 执行命令
        $configNull = DIRECTORY_SEPARATOR === '/' ? '/dev/null' : 'NUL';
        $cmd = $ytDlpBin . ' --config-location ' . $configNull . ' ' . $baseParams . $extraParams . ' ' . escapeshellarg($url);
        $this->log("执行命令: {$cmd}");
        
        // 执行并捕获输出
        $output = shell_exec($cmd . " 2>&1");
        
        if (!$output) {
            throw new \RuntimeException('yt-dlp执行失败或未返回任何输出');
        }
        
        // 检查是否有错误
        if (stripos($output, 'error') !== false) {
            $this->log("yt-dlp返回错误: " . substr($output, 0, 200));
            throw new \RuntimeException('yt-dlp返回错误: ' . substr($output, 0, 100));
        }
        
        // 解析JSON
        $json = json_decode($output, true);
        if (!$json) {
            $this->log("JSON解析失败: " . substr($output, 0, 200));
            throw new \RuntimeException('无法解析yt-dlp的JSON输出');
        }
        
        // 提取视频格式
        $formats = [];
        if (isset($json['formats']) && is_array($json['formats'])) {
            foreach ($json['formats'] as $format) {
                if (isset($format['url'])) {
                    // 解析格式信息
                    $quality = $format['format_note'] ?? $format['format'] ?? '未知品质';
                    $resolution = '';
                    if (isset($format['width']) && isset($format['height'])) {
                        $resolution = "{$format['width']}x{$format['height']}";
                        $quality .= " ({$resolution})";
                    }
                    
                    if (isset($format['tbr'])) {
                        $quality .= " - " . round($format['tbr']) . "kbps";
                    }
                    
                    $formats[] = [
                        'quality' => $quality,
                        'url' => $format['url'],
                        'size' => isset($format['filesize']) ? $this->formatSize($format['filesize']) : '未知',
                        'ext' => $format['ext'] ?? 'mp4',
                        'resolution' => $resolution,
                        'format_id' => $format['format_id'] ?? '',
                        'vcodec' => $format['vcodec'] ?? '',
                        'acodec' => $format['acodec'] ?? '',
                    ];
                }
            }
        } else if (isset($json['url'])) {
            // 处理单格式情况
            $formats[] = [
                'quality' => '默认品质',
                'url' => $json['url'],
                'size' => isset($json['filesize']) ? $this->formatSize($json['filesize']) : '未知',
                'ext' => $json['ext'] ?? 'mp4',
                'resolution' => '',
                'format_id' => $json['format_id'] ?? '',
            ];
        }
        
        // 按品质排序
        usort($formats, function($a, $b) {
            $a_height = 0;
            $b_height = 0;
            
            if (!empty($a['resolution'])) {
                $parts = explode('x', $a['resolution']);
                if (isset($parts[1])) {
                    $a_height = intval($parts[1]);
                }
            }
            
            if (!empty($b['resolution'])) {
                $parts = explode('x', $b['resolution']);
                if (isset($parts[1])) {
                    $b_height = intval($parts[1]);
                }
            }
            
            return $b_height - $a_height;
        });
        
        $this->log("找到 " . count($formats) . " 个可下载格式");
        
        // 构建返回数据
        return [
            'title' => $json['title'] ?? '未知标题',
            'uploader' => $json['uploader'] ?? $json['channel'] ?? '未知上传者',
            'webpage_url' => $json['webpage_url'] ?? $url,
            'thumbnail' => $json['thumbnail'] ?? '',
            'description' => $json['description'] ?? '',
            'duration' => isset($json['duration']) ? $this->formatDuration($json['duration']) : '未知',
            'upload_date' => $this->formatDate($json['upload_date'] ?? ''),
            'view_count' => $json['view_count'] ?? 0,
            'like_count' => $json['like_count'] ?? 0,
            'formats' => $formats,
            'extractor' => $json['extractor'] ?? 'generic',
        ];
    }
    
    /**
     * 查找yt-dlp可执行文件
     */
    private function findYtDlpExecutable() {
        $candidates = [];
        
        // 根据操作系统选择候选路径
        if (DIRECTORY_SEPARATOR === '/') {
            // Linux/Unix/Mac
            $candidates = [
                dirname(dirname(__DIR__)) . '/bin/yt-dlp',
                'yt-dlp',
                '/usr/local/bin/yt-dlp',
                '/usr/bin/yt-dlp',
            ];
        } else {
            // Windows
            $candidates = [
                dirname(dirname(__DIR__)) . '/bin/yt-dlp.exe',
                dirname(dirname(__DIR__)) . '/bin/yt-dlp',
                'yt-dlp.exe',
                'yt-dlp',
            ];
        }
        
        // 测试每个候选路径
        foreach ($candidates as $bin) {
            $cmd = escapeshellarg($bin) . ' --version';
            $output = @shell_exec($cmd);
            
            if ($output && !empty(trim($output))) {
                $this->log("找到yt-dlp: {$bin}, 版本: " . trim($output));
                return $bin;
            }
        }
        
        throw new \RuntimeException('找不到yt-dlp可执行文件，请确保它已安装并在PATH中，或位于bin目录下');
    }
    
    /**
     * 检测网站类型
     */
    private function detectSiteType($url) {
        $domain = parse_url($url, PHP_URL_HOST);
        if (!$domain) {
            return 'generic';
        }
        
        // 去除www前缀
        $domain = preg_replace('/^www\./', '', $domain);
        
        // 常见网站映射
        $siteMap = [
            'youtube.com' => 'youtube.com',
            'youtu.be' => 'youtube.com',
            'twitter.com' => 'twitter.com',
            'x.com' => 'twitter.com',
            'bilibili.com' => 'bilibili.com',
            'b23.tv' => 'bilibili.com',
            'instagram.com' => 'instagram.com',
            'facebook.com' => 'facebook.com',
            'fb.com' => 'facebook.com',
            'tiktok.com' => 'tiktok.com',
        ];
        
        return $siteMap[$domain] ?? 'generic';
    }
    
    /**
     * 清理URL
     */
    private function cleanUrl($url) {
        $url = trim($url);
        
        // 移除URL末尾的问号和参数
        $url = preg_replace('/\?.*$/', '', $url);
        
        return $url;
    }
    
    /**
     * 记录日志
     */
    private function log($message) {
        $time = date('Y-m-d H:i:s');
        $line = "[{$time}] {$message}";
        $this->logs[] = $line;
        
        // 如果在CLI模式下，输出到终端
        if (php_sapi_name() === 'cli') {
            echo $line . PHP_EOL;
        } else {
            // 否则写入PHP错误日志
            error_log($line);
        }
    }
    
    /**
     * 格式化文件大小
     */
    private function formatSize($bytes) {
        if ($bytes < 1024) {
            return "{$bytes} B";
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . " KB";
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . " MB";
        } else {
            return round($bytes / 1073741824, 2) . " GB";
        }
    }
    
    /**
     * 格式化视频时长
     */
    private function formatDuration($seconds) {
        // 确保使用整数
        $seconds = (int)$seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        } else {
            return sprintf("%02d:%02d", $minutes, $seconds);
        }
    }
    
    /**
     * 格式化日期
     */
    private function formatDate($date) {
        if (strlen($date) == 8) {
            // 格式为YYYYMMDD
            return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        }
        return $date;
    }
} 