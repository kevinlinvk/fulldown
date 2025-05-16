<?php
// 包含核心文件
require_once 'includes/config.php';
require_once 'includes/plugin_loader.php';

// 初始化插件加载器
$pluginLoader = new PluginLoader();
$plugins = $pluginLoader->loadPlugins();

// 页面标题
$pageTitle = '全能下载助手';

// 包含头部模板
include 'templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center mb-0">全能下载助手</h3>
                </div>
                <div class="card-body">
                    <form id="download-form" method="post" action="">
                        <div class="form-group">
                            <label for="url">请输入链接地址:</label>
                            <input type="text" class="form-control" id="url" name="url" placeholder="粘贴您要下载的视频链接..." required>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary btn-block w-100">获取下载链接</button>
                        </div>
                    </form>
                    
                    <!-- 插件按钮区域 -->
                    <div class="mt-4">
                        <div class="d-flex flex-wrap justify-content-center plugin-buttons">
                            <?php foreach ($plugins as $plugin): ?>
                                <button class="btn btn-outline-primary m-1 plugin-btn" data-plugin="<?php echo $plugin['id']; ?>">
                                    <?php echo $plugin['name']; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 结果显示区域 -->
                    <div class="mt-4" id="result-area" style="display: none;">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                下载链接
                            </div>
                            <div class="card-body" id="result-content">
                                <!-- 结果将在这里显示 -->
                            </div>
                            <div class="card-body" id="log-content" style="font-size:12px; color:#888; background:#f9f9f9; display:none;">
                                <!-- 日志将在这里显示 -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 包含底部模板
include 'templates/footer.php';
?> 