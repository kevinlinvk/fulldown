<?php
/**
 * 多源视频下载插件
 * 支持youtube-dl/yt-dlp所有支持的视频网站
 */
class MultiDownloaderPlugin extends PluginBase {
    /**
     * 日志缓存
     */
    private $logs = [];
    
    /**
     * 支持的网站列表（部分常用）
     */
    private $supportedSites = [
        'youtube.com', 'youtu.be',
        'bilibili.com', 'b23.tv',
        'vimeo.com',
        'facebook.com', 'fb.com',
        'instagram.com',
        'tiktok.com',
        'twitter.com', 'x.com',
        'twitch.tv',
        'dailymotion.com',
        'reddit.com',
        'pornhub.com',
        'xvideos.com',
        // 可以根据需要添加更多
    ];
    
    /**
     * 获取插件名称
     */
    public function getName() {
        return '多源视频下载';
    }
    
    /**
     * 获取插件描述
     */
    public function getDescription() {
        return '支持1000+视频网站，包括YouTube、哔哩哔哩、推特、TikTok等';
    }
    
    /**
     * 检查是否支持该URL
     */
    public function supportsUrl($url) {
        $cleanUrl = $this->cleanUrl($url);
        
        foreach ($this->supportedSites as $site) {
            if (strpos($cleanUrl, $site) !== false) {
                return true;
            }
        }
        
        // 即使不在预定义列表中，也可以尝试解析
        return true;
    }
    
    /**
     * 处理URL
     */
    public function processUrl($url) {
        $this->logs = [];
        $this->log("原始 URL: {$url}");

        $url = $this->cleanUrl($url);
        $this->log("清理后 URL: {$url}");

        try {
            $videoInfo = $this->fetchFromYtDlp($url);
            if (empty($videoInfo['formats'])) {
                throw new \RuntimeException('未能获取任何视频格式');
            }
            $this->log('成功获取视频格式');

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
     * 记录日志
     */
    private function log($message) {
        $time = date('Y-m-d H:i:s');
        $line = "[{$time}] {$message}";
        $this->logs[] = $line;

        // 同时输出到终端或错误日志
        if (php_sapi_name() === 'cli' || defined('STDERR')) {
            // CLI模式或STDERR可用时，直接写入标准错误输出
            file_put_contents('php://stderr', $line . PHP_EOL);
        } else {
            // FPM/CGI模式下，用error_log写入PHP错误日志
            error_log($line);
        }
    }
    
    /**
     * 清理URL
     */
    private function cleanUrl($url) {
        return preg_replace('/\?.*/', '', trim($url));
    }
    
    /**
     * 使用yt-dlp命令行解析视频
     */
    private function fetchFromYtDlp($url) {
        // 优先使用yt-dlp，其次youtube-dl
        $binCandidates = [
            // 项目目录下的yt-dlp（适用于Linux/Mac）
            __DIR__ . '/../../bin/yt-dlp',  
            // 项目目录下的yt-dlp（适用于Windows）
            __DIR__ . '/../../bin/yt-dlp.exe',
            // 环境变量中的yt-dlp
            'yt-dlp',
            // 环境变量中的youtube-dl
            'youtube-dl',
        ];
        $ytDlpBin = null;
        foreach ($binCandidates as $bin) {
            $testCmd = $bin . ' --version';
            $testOut = shell_exec($testCmd);
            if ($testOut && stripos($testOut, 'error') === false) {
                $ytDlpBin = $bin;
                $this->log("检测到可用解析器: {$bin}");
                break;
            }
        }
        if (!$ytDlpBin) {
            throw new \RuntimeException('未检测到可用的yt-dlp或youtube-dl，请检查环境变量或配置绝对路径');
        }

        $cmd = $ytDlpBin . ' -j --no-warnings --no-playlist ' . escapeshellarg($url);
        $this->log("执行命令: {$cmd}");
        $output = shell_exec($cmd);
        if (!$output) {
            throw new \RuntimeException('yt-dlp/youtube-dl 执行失败或未找到视频');
        }
        $json = json_decode($output, true);
        if (!$json) {
            throw new \RuntimeException('yt-dlp/youtube-dl 输出解析失败');
        }

        $formats = [];
        $urlSet = [];
        foreach ($json['formats'] as $f) {
            if (isset($f['url']) && (!isset($f['vcodec']) || $f['vcodec'] !== 'none')) {
                // 去重
                if (in_array($f['url'], $urlSet)) continue;
                $urlSet[] = $f['url'];
                
                // 提取分辨率
                $resolution = '';
                if (isset($f['width']) && isset($f['height'])) {
                    $resolution = "{$f['width']}x{$f['height']}";
                }
                
                // 提取码率
                $bitrate = '';
                if (isset($f['tbr'])) {
                    $bitrate = round($f['tbr']) . ' kbps';
                }
                
                // 提取视频编码
                $vcodec = isset($f['vcodec']) && $f['vcodec'] !== 'none' ? $f['vcodec'] : '';
                
                // 格式化质量标签
                $qualityLabel = $f['format_note'] ?? $f['format_id'];
                if ($resolution) {
                    $qualityLabel .= " ({$resolution})";
                    if ($bitrate) {
                        $qualityLabel .= " - {$bitrate}";
                    }
                }
                
                $formats[] = [
                    'quality' => $qualityLabel,
                    'url'     => $f['url'],
                    'size'    => isset($f['filesize']) ? round($f['filesize'] / 1048576, 2) . ' MB' : '未知',
                    'ext'     => $f['ext'] ?? '',
                    'resolution' => $resolution,
                    'bitrate' => $bitrate,
                    'vcodec' => $vcodec,
                    'format_id' => $f['format_id'] ?? ''
                ];
            }
        }
        
        // 按分辨率和码率从高到低排序
        usort($formats, function($a, $b) {
            // 首先按分辨率排序
            if ($a['resolution'] != $b['resolution']) {
                // 提取高度进行比较
                $a_height = explode('x', $a['resolution'])[1] ?? 0;
                $b_height = explode('x', $b['resolution'])[1] ?? 0;
                return $b_height - $a_height;
            }
            
            // 分辨率相同，按码率排序
            $a_bitrate = (int)$a['bitrate'];
            $b_bitrate = (int)$b['bitrate'];
            return $b_bitrate - $a_bitrate;
        });

        $this->log('共获取到视频格式数: ' . count($formats));

        // 获取视频标题、缩略图等信息
        $title = $json['title'] ?? '未知标题';
        $thumbnail = $json['thumbnail'] ?? '';
        $duration = isset($json['duration']) ? $this->formatDuration($json['duration']) : '未知时长';
        $uploader = $json['uploader'] ?? '未知上传者';
        $uploadDate = $json['upload_date'] ?? '';
        if ($uploadDate) {
            $uploadDate = substr($uploadDate, 0, 4) . '-' . 
                        substr($uploadDate, 4, 2) . '-' . 
                        substr($uploadDate, 6, 2);
        }
        
        return [
            'id'        => $json['id'] ?? '',
            'thumbnail' => $thumbnail,
            'formats'   => $formats,
            'title'     => $title,
            'duration'  => $duration,
            'uploader'  => $uploader,
            'upload_date' => $uploadDate,
            'extractor' => $json['extractor'] ?? '',
            'webpage_url' => $json['webpage_url'] ?? $url
        ];
    }
    
    /**
     * 格式化视频时长
     */
    private function formatDuration($seconds) {
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds - $hours * 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        } else {
            return sprintf('%02d:%02d', $mins, $secs);
        }
    }
} 