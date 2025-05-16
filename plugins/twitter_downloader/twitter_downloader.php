<?php
/**
 * 推特（X）视频下载插件 - 稳定版，添加完整日志支持与配置属性声明，修复方法签名兼容性
 */
class TwitterDownloaderPlugin extends PluginBase {
    /**
     * 插件配置（包含 Twitter Bearer Token）
     */
    private array $config = [];

    /**
     * 日志缓存
     */
    private array $logs = [];

    /**
     * 构造函数：初始化配置，包括 Twitter Bearer Token
     */
    public function __construct() {
        // 将您的 Twitter Bearer Token 填入此处
        $this->config['twitter_bearer_token'] = '';
    }

    public function getName(): string {
        return '推特视频下载';
    }

    public function getDescription(): string {
        return '稳定获取并下载 Twitter(X) 上的视频，且提供详细执行日志';
    }

    public function supportsUrl($url) {
        return (strpos($url, 'twitter.com/') !== false || strpos($url, 'x.com/') !== false);
    }

    /**
     * 主流程：处理 URL 并记录日志
     * 仅使用yt-dlp解析
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
     * 使用yt-dlp或youtube-dl命令行解析推特视频
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

        $configNull = DIRECTORY_SEPARATOR === '/' ? '/dev/null' : 'NUL';
        $cmd = $ytDlpBin . ' --config-location ' . $configNull . ' -j --no-warnings --no-playlist --force-ipv4 --extractor-args "twitter:tweet_mode=extended" ' . escapeshellarg($url);
        $this->log("执行命令: {$cmd}");
        $output = shell_exec($cmd . " 2>&1");
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
                
                // 提取并格式化分辨率信息
                $resolution = '';
                if (isset($f['width']) && isset($f['height'])) {
                    $resolution = "{$f['width']}x{$f['height']}";
                }
                
                // 提取并格式化码率信息
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

        return [
            'tweet_id'  => $json['id'] ?? '',
            'thumbnail' => $json['thumbnail'] ?? '',
            'formats'   => $formats,
            'title'     => $json['title'] ?? ''
        ];
    }

    /**
     * 记录日志，同时输出到终端或错误日志
     * @param string $message 日志消息
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

    private function cleanUrl(string $url): string {
        return preg_replace('/\?.*/', '', trim($url));
    }

    private function extractTweetId(string $url) {
        if (preg_match('#/(?:status|statuses)/(\d+)#', $url, $m)) {
            return $m[1];
        }
        return false;
    }

    private function fetchFromHtml(string $url): ?array {
        $this->log("HTML 获取 URL: {$url}");
        $html = $this->httpGet($url);
        if (!$html) {
            $this->log('HTTP GET 返回空或非 200');
            return null;
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $meta = $xpath->query('//meta[@property="og:video:secure_url"]')->item(0);
        $videoUrl = $meta ? $meta->getAttribute('content') : null;
        $this->log('og:video:secure_url: ' . ($videoUrl ?: '未找到'));

        if (!$videoUrl) {
            $source = $xpath->query('//video//source')->item(0);
            $videoUrl = $source ? $source->getAttribute('src') : null;
            $this->log('从 <video><source> 提取: ' . ($videoUrl ?: '未找到'));
        }
        if (!$videoUrl) {
            return null;
        }

        $thumbMeta = $xpath->query('//meta[@property="og:image"]')->item(0);
        $thumb = $thumbMeta ? $thumbMeta->getAttribute('content') : '';
        $this->log('og:image: ' . ($thumb ?: '未找到'));

        $descMeta = $xpath->query('//meta[@property="og:description"]')->item(0);
        $title = $descMeta ? $descMeta->getAttribute('content') : '';
        $this->log('og:description: ' . ($title ?: '未找到'));

        return [
            'tweet_id'  => $this->extractTweetId($url),
            'thumbnail' => $thumb,
            'formats'   => [[
                'content_type' => 'video/mp4',
                'bitrate'      => null,
                'url'          => $videoUrl
            ]],
            'title'     => $title
        ];
    }

    private function httpGet(string $url): ?string {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible)'
        ]);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http === 200 ? $res : null;
    }
}
