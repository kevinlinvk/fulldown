<?php
/**
 * 哔哩哔哩(B站)视频下载插件
 * 针对B站视频格式和特性进行优化
 */
class BilibiliDownloaderPlugin extends PluginBase {
    /**
     * 日志缓存
     */
    private array $logs = [];

    /**
     * 插件配置
     */
    private array $config = [
        // 是否使用代理
        'use_proxy' => false,
        // 代理地址
        'proxy' => '',
        // B站特定参数
        'bilibili_args' => '--format "bestvideo+bestaudio/best" --concurrent-fragments 5',
        // B站请求头
        'bilibili_headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
            'Referer' => 'https://www.bilibili.com/',
            'Cookie' => '', // 可以在这里添加自己的Cookie，有助于获取高清视频
        ],
        // 支持的URL格式
        'supported_urls' => [
            'bilibili.com/video/',
            'b23.tv/',
            'bilibili.com/bangumi/',
            'bilibili.tv/',
        ],
        // 支持的分p下载
        'support_parts' => true,
        // 是否尝试获取弹幕
        'fetch_danmaku' => true,
    ];

    /**
     * 获取插件名称
     */
    public function getName(): string {
        return '哔哩哔哩视频下载';
    }

    /**
     * 获取插件描述
     */
    public function getDescription(): string {
        return '下载B站(哔哩哔哩)视频，支持各种清晰度和格式，包括4K和HDR视频';
    }

    /**
     * 检查URL是否为B站链接
     */
    public function supportsUrl($url): bool {
        foreach ($this->config['supported_urls'] as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }
        
        // 尝试解析短链接
        if (preg_match('/^https?:\/\/b23\.tv\/\w+/i', $url)) {
            return true;
        }
        
        return false;
    }

    /**
     * 处理URL，获取视频信息和下载链接
     */
    public function processUrl($url) {
        $this->logs = [];
        $this->log("原始 URL: {$url}");

        // 处理B站短链接
        if (preg_match('/^https?:\/\/b23\.tv\/\w+/i', $url)) {
            $url = $this->expandShortUrl($url);
            $this->log("展开短链接: {$url}");
        }

        // 清理URL
        $url = $this->cleanUrl($url);
        $this->log("清理后 URL: {$url}");

        try {
            // 获取视频信息
            $videoInfo = $this->fetchBilibiliVideo($url);
            
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
     * 使用yt-dlp获取B站视频信息
     */
    private function fetchBilibiliVideo($url) {
        // 查找yt-dlp可执行文件
        $ytDlpBin = $this->findYtDlpExecutable();
        $this->log("使用解析器: {$ytDlpBin}");

        // 构建命令行参数
        $baseParams = ' -j --no-warnings --no-playlist';
        
        // 添加B站专用参数
        $extraParams = ' ' . $this->config['bilibili_args'];
        
        // 添加B站专用请求头
        foreach ($this->config['bilibili_headers'] as $key => $value) {
            if (!empty($value)) {
                $extraParams .= ' --add-header "' . $key . ':' . $value . '"';
            }
        }
        
        // 添加代理设置
        if ($this->config['use_proxy'] && !empty($this->config['proxy'])) {
            $extraParams .= ' --proxy ' . escapeshellarg($this->config['proxy']);
            $this->log("使用代理: " . $this->config['proxy']);
        }
        
        // 禁用配置文件，避免编码问题
        $configNull = DIRECTORY_SEPARATOR === '/' ? '/dev/null' : 'NUL';
        
        // 执行命令
        $cmd = $ytDlpBin . ' --config-location ' . $configNull . $baseParams . $extraParams . ' ' . escapeshellarg($url);
        $this->log("执行命令: {$cmd}");
        
        // 执行并捕获输出
        $output = shell_exec($cmd . " 2>&1");
        
        if (!$output) {
            throw new \RuntimeException('yt-dlp执行失败或未返回任何输出');
        }
        
        // 检查是否有错误
        if (stripos($output, 'error') !== false && stripos($output, 'WARNING') === false) {
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
                    
                    // B站特殊处理：跳过音频轨
                    if (isset($format['vcodec']) && $format['vcodec'] === 'none') {
                        continue; // 跳过纯音频格式
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
        
        // 提取B站特有信息
        $bvid = '';
        $aid = 0;
        $cid = 0;
        
        if (isset($json['id'])) {
            $bvid = $json['id'];
        }
        
        if (isset($json['webpage_url'])) {
            if (preg_match('/\/video\/([^\/\?]+)/', $json['webpage_url'], $matches)) {
                $bvid = $matches[1];
            }
        }
        
        // 提取UP主信息
        $uploader = $json['uploader'] ?? '';
        $uploader_id = $json['uploader_id'] ?? '';
        
        $this->log("找到 " . count($formats) . " 个可下载格式");
        
        // 构建返回数据
        return [
            'title' => $json['title'] ?? '未知标题',
            'uploader' => $uploader,
            'uploader_id' => $uploader_id,
            'webpage_url' => $json['webpage_url'] ?? $url,
            'thumbnail' => $json['thumbnail'] ?? '',
            'description' => $json['description'] ?? '',
            'duration' => isset($json['duration']) ? $this->formatDuration($json['duration']) : '未知',
            'upload_date' => $this->formatDate($json['upload_date'] ?? ''),
            'view_count' => $json['view_count'] ?? 0,
            'like_count' => $json['like_count'] ?? 0,
            'formats' => $formats,
            'extractor' => 'bilibili',
            'bvid' => $bvid,
            'aid' => $aid,
            'cid' => $cid,
            'comment_count' => $json['comment_count'] ?? 0,
            // B站特有信息
            'is_bangumi' => (stripos($url, 'bangumi') !== false),
            'has_danmaku' => $this->config['fetch_danmaku'],
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
     * 清理URL
     */
    private function cleanUrl($url) {
        $url = trim($url);
        
        // 保留B站分P信息，但移除其他参数
        if (preg_match('/(.*?\/video\/[^\/\?]+)(\?p=\d+)?/', $url, $matches)) {
            if (isset($matches[2])) {
                return $matches[1] . $matches[2];
            }
            return $matches[1];
        }
        
        // 处理番剧URL
        if (preg_match('/(.*?\/bangumi\/[^\/\?]+\/[^\/\?]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return $url;
    }
    
    /**
     * 展开B站短链接
     */
    private function expandShortUrl($shortUrl) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $shortUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => $this->config['bilibili_headers']['User-Agent'],
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 301 || $httpCode == 302) {
            preg_match('/Location:(.*?)\n/i', $response, $matches);
            if (isset($matches[1])) {
                return trim($matches[1]);
            }
        }
        
        // 如果无法展开，返回原始链接
        return $shortUrl;
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